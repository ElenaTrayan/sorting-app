<?php

namespace Tests\Feature;

use App\Components\ImageReader;
use App\Models\User;
use App\Services\Integrations\IntegrationManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Кабинет пользователя (/cabinet: посты, хештеги, контакты, вишлисты, запасы) и админка
 * (/admin_panel: пользователи, блокировка, подписка, внешние API) — разделение доступа.
 */
class CabinetAndAdminTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $services = null, bool $admin = false): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($admin ? User::ROLE_ADMIN : User::ROLE_USER);
        if (!$admin) {
            $user->syncServices(array_fill_keys($services ?? array_keys(config('subscription.services')), null));
        }

        return $user;
    }

    // ---------------------------------------------------------------- доступ по ролям

    public function test_guests_are_sent_to_login()
    {
        $this->get('/cabinet')->assertRedirect(route('login'));
        $this->get('/admin_panel')->assertRedirect(route('login'));
    }

    public function test_regular_user_gets_cabinet_but_not_admin_panel()
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('cabinet.home'))->assertOk();
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.integrations.index'))->assertForbidden();
    }

    public function test_admin_sees_admin_panel_pages()
    {
        $admin = $this->makeUser(null, true);
        $other = $this->makeUser();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee($other->email);
        $this->actingAs($admin)->get(route('admin.users.show', $other))->assertOk()->assertSee($other->name);
        $this->actingAs($admin)->get(route('admin.integrations.index'))->assertOk()->assertSee('OCR.space');
    }

    public function test_home_redirects_by_role()
    {
        $this->actingAs($this->makeUser(null, true))->get('/home')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->makeUser())->get('/home')->assertRedirect(route('cabinet.home'));
    }

    public function test_admin_can_also_use_the_cabinet()
    {
        $admin = $this->makeUser(null, true);

        $this->actingAs($admin)->get(route('hashtags.index'))->assertOk();
    }

    public function test_user_search_filters_by_status_and_text()
    {
        $admin = $this->makeUser(null, true);
        $blocked = $this->makeUser();
        $blocked->forceFill(['blocked_at' => now()])->save();
        $active = $this->makeUser();

        $this->actingAs($admin)->get(route('admin.users.index', ['status' => 'blocked']))
            ->assertSee($blocked->email)->assertDontSee($active->email);
        $this->actingAs($admin)->get(route('admin.users.index', ['q' => $active->email]))
            ->assertSee($active->email)->assertDontSee($blocked->email);
    }

    // ---------------------------------------------------------------- блокировка

    public function test_admin_blocks_and_unblocks_user()
    {
        $admin = $this->makeUser(null, true);
        $user = $this->makeUser();

        $this->actingAs($admin)->post(route('admin.users.block', $user), ['reason' => 'спам'])
            ->assertRedirect(route('admin.users.show', $user));
        $this->assertTrue($user->fresh()->isBlocked());
        $this->assertSame('спам', $user->fresh()->blocked_reason);

        $this->actingAs($admin)->post(route('admin.users.unblock', $user));
        $this->assertFalse($user->fresh()->isBlocked());
        $this->assertNull($user->fresh()->blocked_reason);
    }

    public function test_admin_cannot_block_self_or_another_admin()
    {
        $admin = $this->makeUser(null, true);
        $otherAdmin = $this->makeUser(null, true);

        $this->actingAs($admin)->post(route('admin.users.block', $admin))->assertSessionHasErrors();
        $this->actingAs($admin)->post(route('admin.users.block', $otherAdmin))->assertSessionHasErrors();
        $this->assertFalse($admin->fresh()->isBlocked());
        $this->assertFalse($otherAdmin->fresh()->isBlocked());
    }

    public function test_regular_user_cannot_block_anyone()
    {
        $user = $this->makeUser();
        $victim = $this->makeUser();

        $this->actingAs($user)->post(route('admin.users.block', $victim))->assertForbidden();
        $this->assertFalse($victim->fresh()->isBlocked());
    }

    public function test_blocked_user_is_logged_out_with_reason()
    {
        $user = $this->makeUser();
        $user->forceFill(['blocked_at' => now(), 'blocked_reason' => 'нарушение правил'])->save();

        $this->actingAs($user->fresh())->get(route('cabinet.home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_blocked_user_cannot_log_in()
    {
        $user = $this->makeUser();
        $user->forceFill(['blocked_at' => now()])->save();

        // логин с верным паролем проходит, но на первом же запросе блокировка выкидывает
        // обратно на логин — с ошибкой (по ошибке отличаем от "просто не залогинен")
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->get('/home')->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- подписка

    public function test_service_outside_subscription_is_closed_and_hidden_in_menu()
    {
        $user = $this->makeUser(['posts']);

        $this->actingAs($user)->get(route('hashtags.index'))
            ->assertRedirect(route('cabinet.home'))
            ->assertSessionHas('service_denied');
        $this->actingAs($user)->get(route('stock.overview'))->assertRedirect(route('cabinet.home'));
        $this->actingAs($user)->get(route('contacts.index'))->assertRedirect(route('cabinet.home'));
        $this->actingAs($user)->get(route('wishlists.index'))->assertRedirect(route('cabinet.home'));

        $this->actingAs($user)->get(route('posts.index'))->assertOk();

        $home = $this->actingAs($user)->get(route('cabinet.home'))->assertOk();
        $home->assertSee(route('posts.index'), false);
        $home->assertDontSee(route('stock.overview'), false);
    }

    public function test_ajax_to_closed_service_gets_403_json()
    {
        $user = $this->makeUser(['posts']);

        $this->actingAs($user)->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => '*/*'])
            ->post(route('contacts.residence.search'), ['search' => 'Киев'])
            ->assertForbidden()
            ->assertJson(['status' => false]);
    }

    public function test_expired_service_is_closed()
    {
        $user = $this->makeUser([]);
        $user->syncServices(['hashtags' => now()->subDay()]);

        $this->actingAs($user)->get(route('hashtags.index'))->assertRedirect(route('cabinet.home'));

        $user->syncServices(['hashtags' => now()->addDay()]);
        $this->actingAs($user->fresh())->get(route('hashtags.index'))->assertOk();
    }

    public function test_admin_updates_user_subscription()
    {
        $admin = $this->makeUser(null, true);
        $user = $this->makeUser(['posts', 'stock']);

        $this->actingAs($admin)->put(route('admin.users.services.update', $user), [
            'services' => ['hashtags', 'contacts'],
            'expires_at' => ['contacts' => now()->addMonth()->format('Y-m-d')],
        ])->assertRedirect(route('admin.users.show', $user));

        $user = $user->fresh();
        $this->assertEqualsCanonicalizing(['hashtags', 'contacts'], $user->activeServiceKeys());
        $this->assertNotNull($user->services()->where('service', 'contacts')->first()->expires_at);
        $this->assertNull($user->services()->where('service', 'hashtags')->first()->expires_at);
    }

    public function test_unknown_service_is_rejected()
    {
        $admin = $this->makeUser(null, true);
        $user = $this->makeUser([]);

        $this->actingAs($admin)->put(route('admin.users.services.update', $user), ['services' => ['nope']])
            ->assertSessionHasErrors('services.0');
        $this->assertSame([], $user->fresh()->activeServiceKeys());
    }

    public function test_registration_grants_default_services()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post('/register', [
            'name' => 'New One',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('user'));
        $this->assertEqualsCanonicalizing(config('subscription.default_services'), $user->activeServiceKeys());
    }

    // ---------------------------------------------------------------- внешние API

    public function test_integration_key_is_stored_encrypted_and_never_shown()
    {
        $admin = $this->makeUser(null, true);

        $this->actingAs($admin)->put(route('admin.integrations.update', 'ocr_space'), [
            'is_enabled' => '1',
            'credentials' => ['key' => 'SECRET-KEY-123'],
        ])->assertRedirect(route('admin.integrations.index'));

        $manager = app(IntegrationManager::class);
        $this->assertSame('SECRET-KEY-123', $manager->credential('ocr_space', 'key'));
        $this->assertSame('panel', $manager->credentialSource('ocr_space', 'key'));
        $this->assertStringNotContainsString('SECRET-KEY-123', DB::table('api_integrations')->where('key', 'ocr_space')->value('credentials'));

        $this->actingAs($admin)->get(route('admin.integrations.index'))->assertDontSee('SECRET-KEY-123');
        $this->actingAs($admin)->get(route('admin.integrations.edit', 'ocr_space'))->assertDontSee('SECRET-KEY-123');
    }

    public function test_empty_credential_keeps_stored_value_and_clear_removes_it()
    {
        $admin = $this->makeUser(null, true);
        $manager = app(IntegrationManager::class);
        $manager->save('ocr_space', true, ['key' => 'KEEP-ME']);

        $this->actingAs($admin)->put(route('admin.integrations.update', 'ocr_space'), [
            'is_enabled' => '1',
            'credentials' => ['key' => ''],
        ]);
        $this->assertSame('KEEP-ME', app(IntegrationManager::class)->credential('ocr_space', 'key'));

        $this->actingAs($admin)->put(route('admin.integrations.update', 'ocr_space'), [
            'is_enabled' => '1',
            'clear' => ['key'],
        ]);
        $this->assertNotSame('panel', app(IntegrationManager::class)->credentialSource('ocr_space', 'key'));
        $this->assertNotSame('KEEP-ME', app(IntegrationManager::class)->credential('ocr_space', 'key'));
    }

    public function test_disabled_integration_is_off_and_ocr_refuses_to_run()
    {
        $admin = $this->makeUser(null, true);

        $this->assertTrue(app(IntegrationManager::class)->isEnabled('ocr_space')); // по умолчанию включено

        $this->actingAs($admin)->put(route('admin.integrations.update', 'ocr_space'), ['is_enabled' => '0']);
        $this->assertFalse(app(IntegrationManager::class)->isEnabled('ocr_space'));

        $this->expectExceptionMessage('отключена администратором');
        (new ImageReader())->recognizeText('fake-bytes');
    }

    public function test_unknown_integration_is_404()
    {
        $admin = $this->makeUser(null, true);

        $this->actingAs($admin)->get(route('admin.integrations.edit', 'nope'))->assertNotFound();
        $this->actingAs($admin)->put(route('admin.integrations.update', 'nope'), [])->assertNotFound();
    }

    // ---------------------------------------------------------------- изоляция данных

    public function test_user_cannot_touch_another_users_posts_categories_hashtags()
    {
        $owner = $this->makeUser();
        $intruder = $this->makeUser();

        $categoryId = DB::table('posts_categories')->insertGetId([
            'title' => 'Чужая', 'alias' => 'chuzhaya', 'parent_id' => 0, 'user_id' => $owner->id, 'status' => 'active',
        ]);
        $postId = DB::table('posts')->insertGetId([
            'title' => 'Чужой пост', 'alias' => 'chuzhoy-post', 'user_id' => $owner->id, 'category_id' => $categoryId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $hashtagId = DB::table('hashtags')->insertGetId(['title' => 'chuzhoy', 'parent_id' => 0, 'user_id' => $owner->id]);

        $this->actingAs($intruder)->get(route('posts.edit', $postId))->assertNotFound();
        $this->actingAs($intruder)->get(route('posts.show', $postId))->assertNotFound();
        $this->actingAs($intruder)->delete(route('posts.destroy', $postId))->assertNotFound();
        $this->actingAs($intruder)->delete(route('posts-categories.destroy', $categoryId))->assertNotFound();
        $this->actingAs($intruder)->delete(route('hashtags.destroy', $hashtagId))->assertNotFound();

        $this->assertDatabaseHas('posts', ['id' => $postId]);
        $this->assertDatabaseHas('posts_categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('hashtags', ['id' => $hashtagId]);

        // владелец — видит свои
        $this->actingAs($owner)->get(route('posts.edit', $postId))->assertOk();
    }

    public function test_posts_index_lists_only_own_posts()
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();

        foreach ([[$owner, 'MinePost'], [$other, 'TheirsPost']] as [$u, $title]) {
            DB::table('posts')->insert([
                'title' => $title, 'alias' => strtolower($title), 'user_id' => $u->id, 'category_id' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->actingAs($owner)->get(route('posts.index'))->assertOk()->assertSee('MinePost')->assertDontSee('TheirsPost');
        $this->actingAs($other)->get(route('posts.index'))->assertOk()->assertSee('TheirsPost')->assertDontSee('MinePost');
    }

    // ---------------------------------------------------------------- "Считать текст с изображения" (создание поста)

    public function test_get_text_from_image_rejects_foreign_and_traversal_paths()
    {
        // фейковый диск — тест не должен писать в реальный storage/app/public (id тестовых
        // пользователей могут совпасть с id реальных)
        $disk = \Illuminate\Support\Facades\Storage::fake('local');
        $user = $this->makeUser();
        $other = $this->makeUser();
        $disk->put('temp_directory/' . $other->id . '/secret.png', 'x');

        foreach (['temp_directory/' . $other->id . '/secret.png', 'temp_directory/' . $user->id . '/../' . $other->id . '/secret.png', '../.env'] as $path) {
            $this->actingAs($user)->postJson(route('get-text-from-image'), ['image_paths' => [$path]])
                ->assertOk()->assertJson(['status' => false]);
        }

    }

    public function test_get_text_from_image_requires_paths_and_reports_disabled_ocr()
    {
        $disk = \Illuminate\Support\Facades\Storage::fake('local');
        $user = $this->makeUser();

        $this->actingAs($user)->postJson(route('get-text-from-image'), [])->assertStatus(422);

        app(IntegrationManager::class)->save('ocr_space', false);
        $disk->put('temp_directory/' . $user->id . '/mine.png', 'not-really-an-image');

        $this->actingAs($user)->postJson(route('get-text-from-image'), ['image_paths' => ['temp_directory/' . $user->id . '/mine.png']])
            ->assertOk()
            ->assertJson(['status' => false, 'message' => 'Распознавание текста сейчас отключено администратором.']);

    }

    // ---------------------------------------------------------------- страницы кабинета после переноса

    /**
     * Все страницы разделов открываются в кабинете (view-имена/namespace после переноса
     * из админки не поломались) — и у пользователя, и у админа.
     *
     * @dataProvider cabinetPages
     */
    public function test_cabinet_pages_render($routeName)
    {
        $this->actingAs($this->makeUser())->get(route($routeName))->assertOk();
    }

    public function cabinetPages(): array
    {
        return array_map(function ($name) {
            return [$name];
        }, [
            'cabinet.home',
            'posts.index', 'posts.create', 'posts-categories.index', 'posts-categories.create',
            'hashtags.index', 'hashtags.create',
            'wishlists.index', 'contacts.index',
            'media-parser', 'instagram-parser-settings',
            'stock.overview', 'stock.inventory.index', 'stock.shopping-list.index', 'stock.regular-purchases.index',
            'stock.stores.index', 'stock.promotions.index', 'stock.orders.index', 'stock.reserve.index',
            'stock.history.index', 'stock.settings.index',
        ]);
    }
}
