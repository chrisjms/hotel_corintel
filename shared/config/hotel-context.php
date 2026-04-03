<?php
/**
 * Hotel Context - Multi-tenant hotel resolution
 *
 * Resolves the active hotel via two strategies (in priority order):
 *
 * 1. Subdomain detection (VPS / production):
 *    hothello.ovh              -> showcase (main site)
 *    superadmin.hothello.ovh   -> super admin panel
 *    [slug].hothello.ovh       -> hotel client site
 *    admin-[slug].hothello.ovh -> hotel admin panel
 *
 * 2. URL parameter fallback (Render.com / staging / localhost):
 *    ?hotel=slug               -> resolves hotel by slug
 *    No parameter              -> error "Hotel introuvable"
 *
 * Once resolved, sets PostgreSQL search_path to the hotel's schema
 * (hotel_{slug}) if one exists, enabling per-schema data isolation.
 */

require_once __DIR__ . '/database.php';

class HotelContext {
    private static ?HotelContext $instance = null;

    private ?int $hotelId = null;
    private ?string $slug = null;
    private ?string $context = null; // 'showcase' | 'superadmin' | 'hotel-client' | 'hotel-admin'
    private ?string $siteUrl = null;
    private ?string $adminUrl = null;
    private ?array $hotel = null;
    private bool $resolved = false;

    private ?string $schemaName = null;

    private const BASE_DOMAIN = 'hothello.ovh';

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function resolve(): void {
        if ($this->resolved) return;
        $this->resolved = true;

        $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
        // Strip port if present
        $host = preg_replace('/:\d+$/', '', $host);

        $baseDomain = self::BASE_DOMAIN;

        // --- Priority 1: Subdomain detection (VPS / production) ---

        // Localhost / dev: use ?hotel=slug to select hotel, ?context=admin for admin panel
        if ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
            $this->siteUrl = 'http://' . $_SERVER['HTTP_HOST'];
            $this->adminUrl = $this->siteUrl;
            $localContext = ($_GET['context'] ?? '') === 'admin' ? 'hotel-admin' : 'hotel-client';
            $this->resolveFromUrlParam($localContext);
            return;
        }

        // Exact match: hothello.ovh or www.hothello.ovh
        if ($host === $baseDomain || $host === 'www.' . $baseDomain) {
            $this->context = 'showcase';
            $this->siteUrl = 'https://' . $baseDomain;
            return;
        }

        // Subdomain of hothello.ovh
        $suffix = '.' . $baseDomain;
        if (str_ends_with($host, $suffix)) {
            $subdomain = substr($host, 0, -strlen($suffix));

            // superadmin.hothello.ovh
            if ($subdomain === 'superadmin') {
                $this->context = 'superadmin';
                $this->siteUrl = 'https://superadmin.' . $baseDomain;
                return;
            }

            // admin-[slug].hothello.ovh
            if (str_starts_with($subdomain, 'admin-')) {
                $this->slug = substr($subdomain, 6);
                $this->context = 'hotel-admin';
            } else {
                // [slug].hothello.ovh
                $this->slug = $subdomain;
                $this->context = 'hotel-client';
            }

            if ($this->slug) {
                $this->loadHotel($this->slug);
            }
            return;
        }

        // --- Priority 2: URL parameter ?hotel=slug (Render.com / staging) ---
        // Unknown domain (e.g. hothello-client.onrender.com, hothello-admin.onrender.com)
        $this->siteUrl = 'https://' . $host;
        $this->adminUrl = $this->siteUrl;

        // Detect context from hostname: if it contains "admin", it's the admin panel
        $detectedContext = (str_contains($host, 'admin')) ? 'hotel-admin' : 'hotel-client';
        $this->resolveFromUrlParam($detectedContext);
    }

    /**
     * Resolve hotel from ?hotel=slug URL parameter.
     * Used on Render.com and localhost where subdomains are not available.
     */
    private function resolveFromUrlParam(string $defaultContext): void {
        $slugParam = $_GET['hotel'] ?? null;

        if ($slugParam) {
            // Sanitize: only lowercase alphanumeric and hyphens
            $slugParam = preg_replace('/[^a-z0-9-]/', '', strtolower($slugParam));
            $this->slug = $slugParam;
            $this->context = $defaultContext;
            $this->loadHotel($this->slug);
        } else {
            // No hotel specified — show error page
            $this->context = $defaultContext;
            $this->hotelId = null;
            $this->slug = null;
        }
    }

    private function loadHotel(string $slug): void {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare('SELECT * FROM public.hotels WHERE slug = ? AND is_active = TRUE');
            $stmt->execute([$slug]);
            $this->hotel = $stmt->fetch() ?: null;

            if ($this->hotel) {
                $this->hotelId = (int)$this->hotel['id'];
                $this->schemaName = $this->hotel['schema_name'] ?? null;
                $this->siteUrl = $this->hotel['site_url'] ?: ('https://' . $slug . '.' . self::BASE_DOMAIN);
                $this->adminUrl = $this->hotel['admin_url'] ?: ('https://admin-' . $slug . '.' . self::BASE_DOMAIN);

                // Set search_path to hotel schema if it exists
                if ($this->schemaName) {
                    $pdo->exec('SET search_path TO ' . $this->schemaName . ', public');
                }
            } else {
                // Hotel not found
                $this->hotelId = null;
                $this->siteUrl = 'https://' . $slug . '.' . self::BASE_DOMAIN;
                $this->adminUrl = 'https://admin-' . $slug . '.' . self::BASE_DOMAIN;
            }
        } catch (PDOException $e) {
            error_log('HotelContext::loadHotel error: ' . $e->getMessage());
            $this->hotelId = null;
        }
    }

    public function getHotelId(): ?int {
        return $this->hotelId;
    }

    public function getSlug(): ?string {
        return $this->slug;
    }

    public function getContext(): ?string {
        return $this->context;
    }

    public function getSiteUrl(): string {
        return $this->siteUrl ?? '';
    }

    public function getAdminUrl(): string {
        return $this->adminUrl ?? '';
    }

    public function getHotel(): ?array {
        return $this->hotel;
    }

    public function isShowcase(): bool {
        return $this->context === 'showcase';
    }

    public function isSuperAdmin(): bool {
        return $this->context === 'superadmin';
    }

    public function isHotelClient(): bool {
        return $this->context === 'hotel-client';
    }

    public function isHotelAdmin(): bool {
        return $this->context === 'hotel-admin';
    }

    public function getSchemaName(): ?string {
        return $this->schemaName;
    }

    public function requireHotel(): void {
        if ($this->hotelId === null) {
            http_response_code(404);
            $slug = htmlspecialchars($this->slug ?? '');
            die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hotel introuvable</title>
            <style>body{font-family:system-ui,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#FAF6F0;color:#333;}
            .error{text-align:center;padding:2rem;}.error h1{font-size:3rem;color:#8B6F47;margin-bottom:0.5rem;}.error p{font-size:1.1rem;opacity:0.7;}</style></head>
            <body><div class="error"><h1>Hotel introuvable</h1><p>' . ($slug ? "Aucun hotel actif avec le slug \"$slug\"." : "Aucun hotel spécifié. Ajoutez ?hotel=slug à l'URL.") . '</p></div></body></html>');
        }
    }
}
