<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthSessionRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private array $cookies = [];
    private string $clientIp = '192.0.2.20';

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database', 'session.connection' => config('database.default'),
            'session.cookie' => 'conecta_session_recovery_test', 'session.secure' => false,
            'session.lottery' => [0, 100], 'session.lifetime' => 120, 'logging.default' => 'null']);
        // Activar CSRF real después de las guardas de base de Tests\TestCase.
        $this->app['env'] = 'production';
        Mail::fake();
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-11-02 15:00:00', 'UTC'));
    }

    private function browser(string $method, string $uri, array $data = [], bool $acceptCookies = true)
    {
        // Guard y Store nuevos: la siguiente petición debe leer la sesión de DB.
        Auth::forgetGuards();
        $this->app->forgetInstance('auth.driver');
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        // Redirector retiene el Store; renovarlo junto a la sesión de la petición.
        $this->app->forgetInstance('redirect');
        $response = $this->call($method, $uri, $data, $this->cookies, [], [
            'REMOTE_ADDR' => $this->clientIp, 'HTTP_USER_AGENT' => 'Conecta session regression',
        ]);
        if ($acceptCookies) {
            foreach ($response->headers->getCookies() as $cookie) {
                if ($cookie->getExpiresTime() > 0 && $cookie->getExpiresTime() <= now()->timestamp) {
                    unset($this->cookies[$cookie->getName()]);
                } else {
                    $this->cookies[$cookie->getName()] = $cookie->getValue();
                }
            }
        }
        return $response;
    }

    private function form(array $oldData = []): string
    {
        $response = $this->browser('GET', '/')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $token = (new \DOMXPath($document))->query('//form//input[@name="_token"]')->item(0)->getAttribute('value');
        $id = session()->getId();
        $row = DB::table('sessions')->where('id', $id)->first();
        $this->assertNotNull($row);
        if ($oldData) {
            $payload = unserialize(base64_decode($row->payload), ['allowed_classes' => false]);
            DB::table('sessions')->where('id', $id)->update([
                'payload' => base64_encode(serialize(array_merge($payload, $oldData))),
            ]);
        }
        return $token;
    }

    private function login(User $user, string $token, bool $remember = false)
    {
        return $this->browser('POST', '/login', ['_token' => $token,
            'email' => $user->email, 'password' => 'password', 'remember' => $remember]);
    }

    public static function usersAndRemember(): array
    {
        $cases = [];
        foreach (['admin', 'support', 'user'] as $role) {
            foreach ([false, true] as $remember) $cases[$role.' remember='.(int)$remember] = [$role, $remember];
        }
        return $cases;
    }

    #[DataProvider('usersAndRemember')]
    public function test_correct_login_renews_old_ip_and_activity_without_clearing_site_cookies(string $role, bool $remember): void
    {
        $user = User::factory()->create(['role' => $role]);
        $before = $user->only(['id', 'email', 'password', 'role', 'is_active']);
        $token = $this->form(['user_ip' => '192.0.2.10', 'last_activity_at' => now()->subHours(3),
            'draft_marker' => 'conservar', 'url.intended' => route('profile.index')]);
        $oldId = session()->getId();
        $this->login($user, $token, $remember)->assertRedirect(route('profile.index'));
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertSame($this->clientIp, session('user_ip'));
        $this->assertTrue(now()->equalTo(Carbon::parse(session('last_activity_at'))));
        $this->browser('GET', '/tickets')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('conservar', session('draft_marker'));
        $this->assertEquals($before, $user->fresh()->only(array_keys($before)));
        $this->assertDatabaseHas('login_attempts', ['email' => $user->email, 'successful' => true]);
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
    }

    public static function rememberCases(): array
    {
        return ['normal' => [false], 'recordarme' => [true]];
    }

    #[DataProvider('rememberCases')]
    public function test_ip_change_ends_only_the_old_session_shows_the_notice_and_allows_login_again(bool $remember): void
    {
        $user = User::factory()->administrador()->create();
        $token = $this->form();
        $this->login($user, $token, $remember)->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $oldId = session()->getId();
        $oldToken = session()->token();
        $this->clientIp = '192.0.2.30';
        $this->browser('GET', '/tickets')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->assertNull(session('user_ip'));
        $this->assertNull(session('last_activity_at'));
        $this->assertNotSame($oldId, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
        $this->browser('GET', '/')->assertOk()->assertSee('Sesión invalidada por cambio de IP.');
        $token = $this->form();
        $this->login($user, $token, $remember)->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($this->clientIp, session('user_ip'));
    }

    public function test_register_also_renews_old_session_metadata_and_keeps_the_default_user_role(): void
    {
        $department = Department::create(['name' => 'Departamento ficticio', 'is_active' => true]);
        $token = $this->form(['user_ip' => '192.0.2.10', 'last_activity_at' => now()->subHours(3)]);
        $oldId = session()->getId();
        $this->browser('POST', '/register', ['_token' => $token, 'name' => 'Persona Ficticia',
            'email' => 'session-register@example.invalid', 'password' => 'Segura#8',
            'password_confirmation' => 'Segura#8', 'department_id' => $department->id])
            ->assertRedirect('/tickets')->assertSessionHas('success');
        $this->assertNotSame($oldId, session()->getId());
        $this->assertSame($this->clientIp, session('user_ip'));
        $this->assertTrue(now()->equalTo(Carbon::parse(session('last_activity_at'))));
        $this->browser('GET', '/tickets')->assertOk();
        $user = User::where('email', 'session-register@example.invalid')->sole();
        $this->assertSame('user', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_cookie_can_restore_access_without_the_session_cookie(): void
    {
        $user = User::factory()->create();
        $this->login($user, $this->form(), true)->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $rememberName = Auth::guard()->getRecallerName();
        $this->assertArrayHasKey($rememberName, $this->cookies);
        unset($this->cookies[config('session.cookie')]);
        $this->browser('GET', '/tickets')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($this->clientIp, session('user_ip'));
    }

    public function test_incorrect_password_does_not_reset_session_security_metadata(): void
    {
        $user = User::factory()->create();
        $oldActivity = now()->subHours(3);
        $token = $this->form(['user_ip' => '192.0.2.10', 'last_activity_at' => $oldActivity]);
        $this->browser('POST', '/login', ['_token' => $token, 'email' => $user->email,
            'password' => 'incorrect-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame('192.0.2.10', session('user_ip'));
        $this->assertTrue($oldActivity->equalTo(Carbon::parse(session('last_activity_at'))));
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertDatabaseHas('login_attempts', ['email' => $user->email, 'successful' => false]);
    }

    public function test_inactive_account_remains_rejected(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->login($user, $this->form())->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->browser('GET', '/tickets')->assertRedirect(route('login'));
    }

    public function test_logout_still_invalidates_the_session_and_remember_cookie(): void
    {
        $user = User::factory()->create();
        $this->login($user, $this->form(), true)->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $oldId = session()->getId();
        $this->browser('POST', '/logout', ['_token' => session()->token()])->assertRedirect('/');
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
        $this->assertNull(session('user_ip'));
        $this->browser('GET', '/tickets')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_new_login_still_expires_at_the_configured_inactivity_limit(): void
    {
        $user = User::factory()->create();
        $this->login($user, $this->form())->assertRedirect('/tickets');
        $this->travel(120)->minutes();
        $this->browser('GET', '/tickets')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->browser('GET', '/')->assertSee('Tu sesión expiró por inactividad.');
    }

    public function test_disconnect_does_not_remove_other_users_or_their_sessions(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->login($first, $this->form())->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $firstCookies = $this->cookies;
        $firstId = session()->getId();
        $this->cookies = [];
        $this->clientIp = '192.0.2.40';
        $this->login($second, $this->form())->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertOk();
        $secondId = session()->getId();
        $secondRow = (array) DB::table('sessions')->where('id', $secondId)->first();
        $firstBefore = $first->fresh()->only(['id', 'name', 'email', 'password', 'role', 'is_active']);
        $secondBefore = $second->fresh()->getAttributes();
        $this->cookies = $firstCookies;
        $this->clientIp = '192.0.2.30';
        $this->browser('GET', '/tickets')->assertRedirect(route('home'));
        $this->assertDatabaseMissing('sessions', ['id' => $firstId]);
        $this->assertSame($secondRow, (array) DB::table('sessions')->where('id', $secondId)->first());
        $this->assertEquals($firstBefore, $first->fresh()->only(array_keys($firstBefore)));
        $this->assertSame($secondBefore, $second->fresh()->getAttributes());
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_expired_csrf_token_still_cannot_authenticate(): void
    {
        $user = User::factory()->create();
        $this->form();
        $this->login($user, 'token-vencido')->assertStatus(419);
        $this->assertGuest();
        $this->assertDatabaseCount('login_attempts', 0);
    }

    public function test_browser_must_still_send_the_new_session_cookie(): void
    {
        $user = User::factory()->create();
        $token = $this->form();
        $this->browser('POST', '/login', ['_token' => $token, 'email' => $user->email,
            'password' => 'password'], false)->assertRedirect('/tickets');
        $this->browser('GET', '/tickets')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_two_users_on_the_same_network_keep_identity_permissions_and_csrf_separate(): void
    {
        $admin = User::factory()->administrador()->create(['name' => 'Admin Laboratorio']);
        $user = User::factory()->create(['name' => 'Usuario Laboratorio']);
        $this->login($admin, $this->form(), true)->assertRedirect('/tickets');
        $this->browser('GET', route('profile.index'))->assertOk()->assertSee($admin->name)->assertDontSee($user->name);
        $adminCookies = $this->cookies;
        $adminId = session()->getId();
        $adminToken = session()->token();
        $this->cookies = [];
        // Mismo REMOTE_ADDR para ambos: las cookies deben distinguir las sesiones.
        $this->login($user, $this->form(), true)->assertRedirect('/tickets');
        $userCookies = $this->cookies;
        $userId = session()->getId();
        $userToken = session()->token();
        $this->assertNotSame($adminId, $userId);
        $this->assertNotSame($adminToken, $userToken);

        for ($round = 0; $round < 5; $round++) {
            $this->cookies = $adminCookies;
            $this->browser('GET', route('profile.index'))->assertOk()->assertSee($admin->name)->assertDontSee($user->name);
            $this->assertAuthenticatedAs($admin);
            $this->assertSame($adminId, session()->getId());
            $this->assertSame($adminToken, session()->token());
            $adminCookies = $this->cookies;
            $this->cookies = $userCookies;
            $this->browser('GET', route('profile.index'))->assertOk()->assertSee($user->name)->assertDontSee($admin->name);
            $this->assertAuthenticatedAs($user);
            $this->assertSame($userId, session()->getId());
            $this->assertSame($userToken, session()->token());
            $userCookies = $this->cookies;
        }
        $this->browser('GET', route('admin.dashboard'))->assertForbidden();
        $userCookies = $this->cookies;
        $this->cookies = $adminCookies;
        $this->browser('GET', route('admin.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->browser('POST', '/logout', ['_token' => $userToken])->assertStatus(419);
        $this->browser('GET', route('profile.index'))->assertOk();
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('sessions', ['id' => $adminId, 'user_id' => $admin->id]);
        $this->assertDatabaseHas('sessions', ['id' => $userId, 'user_id' => $user->id]);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame('user', $user->fresh()->role);
    }

    public function test_logout_and_account_switch_in_one_browser_preserve_the_other_browser_session(): void
    {
        $first = User::factory()->create(['name' => 'Primera Persona']);
        $second = User::factory()->create(['name' => 'Segunda Persona']);
        $this->login($first, $this->form(), true)->assertRedirect('/tickets');
        $firstBrowser = $this->cookies;
        $oldId = session()->getId();
        $this->cookies = [];
        $this->login($first, $this->form(), true)->assertRedirect('/tickets');
        $secondBrowser = $this->cookies;
        $otherId = session()->getId();
        $otherRow = (array) DB::table('sessions')->where('id', $otherId)->first();
        $this->assertNotSame($oldId, $otherId);

        $this->cookies = $firstBrowser;
        $this->browser('GET', route('profile.index'))->assertOk()->assertSee($first->name);
        $this->browser('POST', '/logout', ['_token' => session()->token()])->assertRedirect('/');
        $this->assertDatabaseMissing('sessions', ['id' => $oldId]);
        $this->assertSame($otherRow, (array) DB::table('sessions')->where('id', $otherId)->first());
        $this->login($second, $this->form())->assertRedirect('/tickets');
        $this->browser('GET', route('profile.index'))->assertOk()->assertSee($second->name)->assertDontSee($first->name);
        $this->assertAuthenticatedAs($second);
        $switchedBrowser = $this->cookies;
        $newId = session()->getId();
        $this->assertNotSame($oldId, $newId);
        $this->assertNotSame($otherId, $newId);

        $this->cookies = $secondBrowser;
        $this->browser('GET', route('profile.index'))->assertOk()->assertSee($first->name)->assertDontSee($second->name);
        $this->assertAuthenticatedAs($first);
        $this->assertSame($otherId, session()->getId());
        $this->cookies = $firstBrowser;
        $this->browser('GET', '/tickets')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->cookies = $switchedBrowser;
        $this->browser('GET', route('profile.index'))->assertOk()->assertSee($second->name);
        $this->assertAuthenticatedAs($second);
        $this->assertDatabaseHas('sessions', ['id' => $otherId, 'user_id' => $first->id]);
        $this->assertDatabaseHas('sessions', ['id' => $newId, 'user_id' => $second->id]);
        $this->assertDatabaseCount('usuarios', 2);
    }
}
