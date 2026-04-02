<?php
/**
 * Hotel Context - Multi-tenant subdomain resolution
 *
 * Parses HTTP_HOST to determine which hotel and context (client/admin/superadmin)
 * the current request belongs to.
 *
 * Subdomain patterns:
 *   hothello.ovh              -> showcase (main site)
 *   superadmin.hothello.ovh   -> super admin panel
 *   [slug].hothello.ovh       -> hotel client site
 *   admin-[slug].hothello.ovh -> hotel admin panel
 *   localhost / 127.0.0.1     -> fallback to hotel_id=1
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

        // Localhost / dev fallback
        if ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
            $this->context = 'hotel-client';
            $this->hotelId = 1;
            $this->slug = 'corintel';
            $this->siteUrl = 'http://' . $_SERVER['HTTP_HOST'];
            $this->adminUrl = $this->siteUrl . '/admin';
            return;
        }

        $baseDomain = self::BASE_DOMAIN;

        // Exact match: hothello.ovh or www.hothello.ovh
        if ($host === $baseDomain || $host === 'www.' . $baseDomain) {
            $this->context = 'showcase';
            $this->siteUrl = 'https://' . $baseDomain;
            return;
        }

        // Must be a subdomain of hothello.ovh
        $suffix = '.' . $baseDomain;
        if (!str_ends_with($host, $suffix)) {
            // Unknown domain, fallback
            $this->context = 'hotel-client';
            $this->hotelId = 1;
            $this->slug = 'corintel';
            $this->siteUrl = 'https://' . $host;
            $this->adminUrl = $this->siteUrl . '/admin';
            return;
        }

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

        // Load hotel from DB
        if ($this->slug) {
            $this->loadHotel($this->slug);
        }
    }

    private function loadHotel(string $slug): void {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare('SELECT * FROM hotels WHERE slug = ? AND is_active = TRUE');
            $stmt->execute([$slug]);
            $this->hotel = $stmt->fetch() ?: null;

            if ($this->hotel) {
                $this->hotelId = (int)$this->hotel['id'];
                $this->siteUrl = $this->hotel['site_url'] ?: ('https://' . $slug . '.' . self::BASE_DOMAIN);
                $this->adminUrl = $this->hotel['admin_url'] ?: ('https://admin-' . $slug . '.' . self::BASE_DOMAIN);
            } else {
                // Hotel not found - show 404 or fallback
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

    public function requireHotel(): void {
        if ($this->hotelId === null) {
            http_response_code(404);
            die('Hotel not found.');
        }
    }
}
