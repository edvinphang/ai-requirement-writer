# AI Requirement Writer — Plan 1: Foundation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scaffold the full-stack project (Laravel API + Next.js frontend + MySQL), implement auth, project CRUD, and template management — producing a working, tested app foundation before any AI features are added.

**Architecture:** Laravel 11 API-only backend with Sanctum token auth; Next.js 14 App Router frontend; both in a monorepo under `backend/` and `frontend/`. All backend routes are under `/api/`. Frontend consumes the API via a typed client.

**Tech Stack:** Laravel 11, PHP 8.2+, MySQL 8, Laravel Sanctum, PHPUnit, Next.js 14 (App Router), TypeScript, Tailwind CSS, Vitest, React Testing Library

---

## File Map

### Backend (`backend/`)
| File | Purpose |
|------|---------|
| `app/Models/User.php` | User model with role field and projects relationship |
| `app/Models/Project.php` | Project model with user/template relationships |
| `app/Models/Template.php` | Template model |
| `app/Http/Controllers/AuthController.php` | Register, login, logout |
| `app/Http/Controllers/ProjectController.php` | Project CRUD (scoped to auth user) |
| `app/Http/Controllers/TemplateController.php` | List templates |
| `app/Http/Requests/RegisterRequest.php` | Register validation |
| `app/Http/Requests/StoreProjectRequest.php` | Project creation validation |
| `database/migrations/*_create_templates_table.php` | Templates schema |
| `database/migrations/*_create_projects_table.php` | Projects schema |
| `database/migrations/*_create_requirement_drafts_table.php` | Drafts schema |
| `database/migrations/*_create_project_intake_table.php` | Intake schema |
| `database/migrations/*_create_exports_table.php` | Exports schema |
| `database/migrations/*_create_integrations_table.php` | Integrations schema |
| `database/migrations/*_create_chat_messages_table.php` | Chat schema |
| `database/factories/TemplateFactory.php` | Template test factory |
| `database/factories/ProjectFactory.php` | Project test factory |
| `database/seeders/TemplateSeeder.php` | Seeds 5 project templates |
| `routes/api.php` | All API routes |
| `tests/Feature/AuthTest.php` | Auth endpoint tests |
| `tests/Feature/ProjectTest.php` | Project CRUD tests |
| `tests/Feature/TemplateTest.php` | Template list tests |

### Frontend (`frontend/`)
| File | Purpose |
|------|---------|
| `lib/api.ts` | Typed fetch wrapper with token injection |
| `lib/auth.tsx` | Auth context, useAuth hook, login/register/logout |
| `app/layout.tsx` | Root layout wrapping AuthProvider |
| `app/page.tsx` | Root redirect to /dashboard |
| `app/(auth)/layout.tsx` | Centered card layout for auth pages |
| `app/(auth)/login/page.tsx` | Login form |
| `app/(auth)/register/page.tsx` | Register form |
| `app/(dashboard)/layout.tsx` | Protected layout with nav bar |
| `app/(dashboard)/dashboard/page.tsx` | Project list |
| `app/(dashboard)/projects/new/page.tsx` | New project form with template picker |
| `app/(dashboard)/projects/[id]/page.tsx` | Project detail (placeholder for Plan 2) |
| `components/ProjectCard.tsx` | Project list item component |
| `tests/lib/api.test.ts` | API client unit tests |
| `tests/components/LoginForm.test.tsx` | Login page tests |
| `tests/components/ProjectCard.test.tsx` | ProjectCard unit tests |
| `tests/setup.ts` | Vitest + testing-library setup |

---

## Task 1: Initialize monorepo and install dependencies

**Files:**
- Create: `backend/` (Laravel 11 project)
- Create: `frontend/` (Next.js 14 project)
- Create: `.gitignore`
- Create: `frontend/vitest.config.ts`
- Create: `frontend/tests/setup.ts`

- [ ] **Step 1: Create project root and initialize git**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git init
cat > .gitignore << 'EOF'
backend/vendor/
frontend/node_modules/
backend/.env
frontend/.env.local
*.log
.DS_Store
EOF
```

- [ ] **Step 2: Install Laravel**
```bash
composer create-project laravel/laravel backend --prefer-dist
```
Expected: Laravel 11 project created in `backend/`

- [ ] **Step 3: Install Laravel Sanctum**
```bash
cd backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

- [ ] **Step 4: Install Next.js**
```bash
cd ..
npx create-next-app@latest frontend --typescript --tailwind --eslint --app --no-src-dir --import-alias "@/*" --yes
```
Expected: Next.js 14 project in `frontend/`

- [ ] **Step 5: Install frontend test dependencies**
```bash
cd frontend
npm install -D vitest @vitejs/plugin-react jsdom @testing-library/react @testing-library/jest-dom @testing-library/user-event
```

- [ ] **Step 6: Create `frontend/vitest.config.ts`**
```typescript
import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: './tests/setup.ts',
  },
  resolve: {
    alias: { '@': path.resolve(__dirname, '.') },
  },
})
```

- [ ] **Step 7: Create `frontend/tests/setup.ts`**
```typescript
import '@testing-library/jest-dom'
```

- [ ] **Step 8: Add test scripts to `frontend/package.json`**
In the `"scripts"` block, add:
```json
"test": "vitest",
"test:run": "vitest run"
```

- [ ] **Step 9: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add .gitignore
git add backend/
git add frontend/
git commit -m "chore: initialize Laravel + Next.js monorepo"
```

---

## Task 2: Configure Laravel environment

**Files:**
- Modify: `backend/.env`
- Modify: `backend/config/cors.php`
- Modify: `backend/bootstrap/app.php`
- Modify: `backend/phpunit.xml`

- [ ] **Step 1: Create `backend/.env` from example**
```bash
cp backend/.env.example backend/.env
```
Then edit `backend/.env` and update these values:
```
APP_NAME="AI Requirement Writer"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_requirement_writer
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
FRONTEND_URL=http://localhost:3000
```

- [ ] **Step 2: Create MySQL database**
```bash
mysql -u root -p -e "CREATE DATABASE ai_requirement_writer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
Expected: Database created with no errors

- [ ] **Step 3: Generate app key**
```bash
cd backend && php artisan key:generate
```
Expected: Application key set successfully

- [ ] **Step 4: Replace `backend/config/cors.php`**
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

- [ ] **Step 5: Add Sanctum stateful middleware to `backend/bootstrap/app.php`**
Find the `->withMiddleware(function (Middleware $middleware) {` block and add inside it:
```php
$middleware->statefulApi();
```

- [ ] **Step 6: Configure PHPUnit to use SQLite in-memory — edit `backend/phpunit.xml`**
Inside the `<php>` section, add:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 7: Verify config**
```bash
cd backend
php artisan config:clear
php artisan migrate:status
```
Expected: No errors; shows empty migration list

- [ ] **Step 8: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add backend/config/cors.php backend/bootstrap/app.php backend/phpunit.xml
git commit -m "chore: configure Laravel CORS, Sanctum stateful, SQLite test db"
```

---

## Task 3: Database migrations

**Files:**
- Modify: `backend/database/migrations/0001_01_01_000000_create_users_table.php`
- Create: 7 new migration files via `php artisan make:migration`

- [ ] **Step 1: Add `role` column to users migration**
Open `backend/database/migrations/0001_01_01_000000_create_users_table.php`. After the `$table->string('email')->unique();` line, add:
```php
$table->enum('role', ['admin', 'member'])->default('member');
```

- [ ] **Step 2: Create templates migration**
```bash
cd backend && php artisan make:migration create_templates_table
```
In the new file, replace the `up()` method body:
```php
Schema::create('templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('type');
    $table->json('fields');
    $table->timestamps();
});
```

- [ ] **Step 3: Create projects migration**
```bash
php artisan make:migration create_projects_table
```
Replace the `up()` method body:
```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->enum('type', ['webapp', 'mobile', 'api', 'data', 'custom']);
    $table->enum('mode', ['template', 'conversational'])->default('template');
    $table->enum('status', ['draft', 'in_progress', 'complete'])->default('draft');
    $table->timestamps();
});
```

- [ ] **Step 4: Create requirement_drafts migration**
```bash
php artisan make:migration create_requirement_drafts_table
```
Replace the `up()` method body:
```php
Schema::create('requirement_drafts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['brd', 'stories', 'spec']);
    $table->longText('content');
    $table->unsignedInteger('version')->default(1);
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 5: Create project_intake migration**
```bash
php artisan make:migration create_project_intake_table
```
Replace the `up()` method body:
```php
Schema::create('project_intake', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->json('form_data');
    $table->text('transcript')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 6: Create exports migration**
```bash
php artisan make:migration create_exports_table
```
Replace the `up()` method body:
```php
Schema::create('exports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->enum('format', ['pdf', 'docx', 'markdown']);
    $table->string('file_path');
    $table->timestamps();
});
```

- [ ] **Step 7: Create integrations migration**
```bash
php artisan make:migration create_integrations_table
```
Replace the `up()` method body:
```php
Schema::create('integrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('provider', ['jira', 'confluence', 'notion', 'github']);
    $table->text('credentials');
    $table->timestamps();
    $table->unique(['user_id', 'provider']);
});
```

- [ ] **Step 8: Create chat_messages migration**
```bash
php artisan make:migration create_chat_messages_table
```
Replace the `up()` method body:
```php
Schema::create('chat_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['user', 'assistant']);
    $table->text('content');
    $table->unsignedInteger('order');
    $table->timestamps();
});
```

- [ ] **Step 9: Run migrations**
```bash
php artisan migrate
```
Expected: All migrations run successfully, no errors

- [ ] **Step 10: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add backend/database/migrations/
git commit -m "feat: add full database schema (projects, templates, drafts, intake, exports, integrations, chat)"
```

---

## Task 4: Template model, factory, seeder, and API

**Files:**
- Create: `backend/app/Models/Template.php`
- Create: `backend/database/factories/TemplateFactory.php`
- Create: `backend/database/seeders/TemplateSeeder.php`
- Modify: `backend/database/seeders/DatabaseSeeder.php`
- Create: `backend/app/Http/Controllers/TemplateController.php`
- Create: `backend/tests/Feature/TemplateTest.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Write the failing test — create `backend/tests/Feature/TemplateTest.php`**
```php
<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_templates(): void
    {
        $user = User::factory()->create();
        Template::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/templates');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type', 'fields']]
            ]);
    }

    public function test_unauthenticated_user_cannot_list_templates(): void
    {
        $response = $this->getJson('/api/templates');
        $response->assertUnauthorized();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
```bash
cd backend && php artisan test tests/Feature/TemplateTest.php
```
Expected: FAIL — class or route not found

- [ ] **Step 3: Create `backend/app/Models/Template.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'fields'];
    protected $casts = ['fields' => 'array'];
}
```

- [ ] **Step 4: Create `backend/database/factories/TemplateFactory.php`**
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['webapp', 'mobile', 'api', 'data', 'custom']),
            'fields' => [
                ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
            ],
        ];
    }
}
```

- [ ] **Step 5: Create `backend/app/Http/Controllers/TemplateController.php`**
```php
<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Template::all()]);
    }
}
```

- [ ] **Step 6: Replace `backend/routes/api.php`**
```php
<?php

use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index']);
});
```

- [ ] **Step 7: Run test to verify it passes**
```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: PASS — 2 tests, 2 assertions

- [ ] **Step 8: Create `backend/database/seeders/TemplateSeeder.php`**
```php
<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Web Application',
                'type' => 'webapp',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Mobile Application',
                'type' => 'mobile',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'platform', 'label' => 'Platform (iOS / Android / Both)', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'API / Microservice',
                'type' => 'api',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'consumers', 'label' => 'API Consumers', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Data Pipeline',
                'type' => 'data',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'data_sources', 'label' => 'Data Sources', 'type' => 'textarea', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Custom',
                'type' => 'custom',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}
```

- [ ] **Step 9: Register seeder in `backend/database/seeders/DatabaseSeeder.php`**
Replace the `run()` method:
```php
public function run(): void
{
    $this->call([TemplateSeeder::class]);
}
```

- [ ] **Step 10: Run seeder**
```bash
php artisan db:seed
```
Expected: "Seeding: Database\Seeders\TemplateSeeder" — Done

- [ ] **Step 11: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add backend/app/Models/Template.php \
        backend/database/factories/TemplateFactory.php \
        backend/app/Http/Controllers/TemplateController.php \
        backend/database/seeders/ \
        backend/routes/api.php \
        backend/tests/Feature/TemplateTest.php
git commit -m "feat: add Template model, API, factory, and seeder with 5 project types"
```

---

## Task 5: Auth API (register, login, logout)

**Files:**
- Create: `backend/app/Http/Controllers/AuthController.php`
- Create: `backend/app/Http/Requests/RegisterRequest.php`
- Create: `backend/tests/Feature/AuthTest.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Write the failing test — create `backend/tests/Feature/AuthTest.php`**
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Edvin Test',
            'email' => 'edvin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'edvin@test.com']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'edvin@test.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Edvin Test',
            'email' => 'edvin@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertOk()->assertJson(['message' => 'Logged out']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
```bash
cd backend && php artisan test tests/Feature/AuthTest.php
```
Expected: FAIL — routes not found (404)

- [ ] **Step 3: Create `backend/app/Http/Requests/RegisterRequest.php`**
```bash
php artisan make:request RegisterRequest
```
Replace the file content:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] **Step 4: Create `backend/app/Http/Controllers/AuthController.php`**
```bash
php artisan make:controller AuthController
```
Replace the file content:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['data' => ['user' => $user, 'token' => $token]], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ])->status(401);
        }

        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['data' => ['user' => $user, 'token' => $token]]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
```

- [ ] **Step 5: Update `backend/routes/api.php` to add auth routes**
```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index']);
});
```

- [ ] **Step 6: Run tests to verify they pass**
```bash
php artisan test tests/Feature/AuthTest.php
```
Expected: PASS — 5 tests, 7 assertions

- [ ] **Step 7: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add backend/app/Http/Controllers/AuthController.php \
        backend/app/Http/Requests/RegisterRequest.php \
        backend/routes/api.php \
        backend/tests/Feature/AuthTest.php
git commit -m "feat: add auth API — register, login, logout with Sanctum tokens"
```

---

## Task 6: Project CRUD API

**Files:**
- Create: `backend/app/Models/Project.php`
- Create: `backend/database/factories/ProjectFactory.php`
- Create: `backend/app/Http/Controllers/ProjectController.php`
- Create: `backend/app/Http/Requests/StoreProjectRequest.php`
- Create: `backend/tests/Feature/ProjectTest.php`
- Modify: `backend/app/Models/User.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Write the failing test — create `backend/tests/Feature/ProjectTest.php`**
```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_a_project(): void
    {
        $template = Template::factory()->create(['type' => 'webapp']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/projects', [
                'name' => 'My Web App',
                'type' => 'webapp',
                'template_id' => $template->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'name', 'type', 'status', 'mode']]);

        $this->assertDatabaseHas('projects', [
            'name' => 'My Web App',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_list_their_own_projects(): void
    {
        Project::factory()->count(2)->create(['user_id' => $this->user->id]);
        Project::factory()->create(); // another user's project

        $response = $this->actingAs($this->user)
            ->getJson('/api/projects');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_their_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $project = Project::factory()->create(); // different user

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$project->id}");

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_project(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/projects/{$project->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
```bash
cd backend && php artisan test tests/Feature/ProjectTest.php
```
Expected: FAIL — model or route not found

- [ ] **Step 3: Create `backend/app/Models/Project.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'template_id', 'name', 'type', 'mode', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
```

- [ ] **Step 4: Create `backend/database/factories/ProjectFactory.php`**
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['webapp', 'mobile', 'api', 'data', 'custom']),
            'mode' => 'template',
            'status' => 'draft',
        ];
    }
}
```

- [ ] **Step 5: Add `projects()` relationship to `backend/app/Models/User.php`**
Add the import and method to the User class:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function projects(): HasMany
{
    return $this->hasMany(Project::class);
}
```

- [ ] **Step 6: Create `backend/app/Http/Requests/StoreProjectRequest.php`**
```bash
php artisan make:request StoreProjectRequest
```
Replace the file content:
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['webapp', 'mobile', 'api', 'data', 'custom'])],
            'template_id' => ['nullable', 'exists:templates,id'],
            'mode' => ['nullable', Rule::in(['template', 'conversational'])],
        ];
    }
}
```

- [ ] **Step 7: Create `backend/app/Http/Controllers/ProjectController.php`**
```bash
php artisan make:controller ProjectController --api
```
Replace the file content:
```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()->projects()->latest()->get();

        return response()->json(['data' => $projects]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $request->user()->projects()->create($request->validated());

        return response()->json(['data' => $project], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $project]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $project->delete();

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 8: Add project routes to `backend/routes/api.php`**
Add this import at the top:
```php
use App\Http\Controllers\ProjectController;
```
Add inside the `auth:sanctum` middleware group:
```php
Route::apiResource('projects', ProjectController::class)->except(['update']);
```

- [ ] **Step 9: Run tests to verify they pass**
```bash
php artisan test tests/Feature/ProjectTest.php
```
Expected: PASS — 5 tests, 8 assertions

- [ ] **Step 10: Run full backend test suite**
```bash
php artisan test
```
Expected: All tests pass

- [ ] **Step 11: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add backend/app/Models/Project.php \
        backend/app/Models/User.php \
        backend/database/factories/ProjectFactory.php \
        backend/app/Http/Controllers/ProjectController.php \
        backend/app/Http/Requests/StoreProjectRequest.php \
        backend/routes/api.php \
        backend/tests/Feature/ProjectTest.php
git commit -m "feat: add Project model and CRUD API scoped to authenticated user"
```

---

## Task 7: Next.js API client and auth context

**Files:**
- Create: `frontend/.env.local`
- Create: `frontend/lib/api.ts`
- Create: `frontend/lib/auth.tsx`
- Create: `frontend/tests/lib/api.test.ts`

- [ ] **Step 1: Create `frontend/.env.local`**
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

- [ ] **Step 2: Write the failing test — create `frontend/tests/lib/api.test.ts`**
```typescript
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { apiClient } from '@/lib/api'

describe('apiClient', () => {
  beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn())
    localStorage.clear()
  })

  it('sends Authorization header when token exists in localStorage', async () => {
    localStorage.setItem('token', 'test-token-123')
    const mockResponse = { ok: true, json: async () => ({ data: [] }) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await apiClient.get('/projects')

    expect(fetch).toHaveBeenCalledWith(
      'http://localhost:8000/api/projects',
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer test-token-123',
        }),
      })
    )
  })

  it('does not send Authorization header when no token', async () => {
    const mockResponse = { ok: true, json: async () => ({}) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await apiClient.post('/auth/login', { email: 'a@b.com', password: 'pw' })

    const callArgs = vi.mocked(fetch).mock.calls[0][1] as RequestInit
    expect((callArgs.headers as Record<string, string>)['Authorization']).toBeUndefined()
  })

  it('throws an error when response is not ok', async () => {
    const mockResponse = { ok: false, status: 401, json: async () => ({ message: 'Unauthorized' }) }
    vi.mocked(fetch).mockResolvedValue(mockResponse as Response)

    await expect(apiClient.get('/projects')).rejects.toThrow('Unauthorized')
  })
})
```

- [ ] **Step 3: Run test to verify it fails**
```bash
cd frontend && npm run test:run -- tests/lib/api.test.ts
```
Expected: FAIL — module not found

- [ ] **Step 4: Create `frontend/lib/api.ts`**
```typescript
const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/api'

function getToken(): string | null {
  if (typeof window === 'undefined') return null
  return localStorage.getItem('token')
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const token = getToken()
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }
  if (token) headers['Authorization'] = `Bearer ${token}`

  const res = await fetch(`${BASE_URL}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  })

  const data = await res.json()
  if (!res.ok) throw new Error(data.message ?? 'Request failed')
  return data as T
}

export const apiClient = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body: unknown) => request<T>('POST', path, body),
  delete: <T>(path: string) => request<T>('DELETE', path),
}
```

- [ ] **Step 5: Run test to verify it passes**
```bash
npm run test:run -- tests/lib/api.test.ts
```
Expected: PASS — 3 tests

- [ ] **Step 6: Create `frontend/lib/auth.tsx`**
```typescript
'use client'

import { createContext, useContext, useEffect, useState, ReactNode } from 'react'
import { apiClient } from './api'

interface User {
  id: number
  name: string
  email: string
  role: string
}

interface AuthContextType {
  user: User | null
  token: string | null
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  loading: boolean
}

const AuthContext = createContext<AuthContextType | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [token, setToken] = useState<string | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const stored = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')
    if (stored && storedUser) {
      setToken(stored)
      setUser(JSON.parse(storedUser))
    }
    setLoading(false)
  }, [])

  async function login(email: string, password: string) {
    const res = await apiClient.post<{ data: { user: User; token: string } }>(
      '/auth/login',
      { email, password }
    )
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    setToken(res.data.token)
    setUser(res.data.user)
  }

  async function register(name: string, email: string, password: string) {
    const res = await apiClient.post<{ data: { user: User; token: string } }>(
      '/auth/register',
      { name, email, password, password_confirmation: password }
    )
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    setToken(res.data.token)
    setUser(res.data.user)
  }

  async function logout() {
    await apiClient.post('/auth/logout', {})
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    setToken(null)
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, token, login, register, logout, loading }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used inside AuthProvider')
  return ctx
}
```

- [ ] **Step 7: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add frontend/lib/api.ts frontend/lib/auth.tsx frontend/tests/lib/api.test.ts
git commit -m "feat: add typed API client and auth context with localStorage token management"
```

---

## Task 8: Auth pages (login + register)

**Files:**
- Modify: `frontend/app/layout.tsx`
- Create: `frontend/app/(auth)/layout.tsx`
- Create: `frontend/app/(auth)/login/page.tsx`
- Create: `frontend/app/(auth)/register/page.tsx`
- Create: `frontend/tests/components/LoginForm.test.tsx`

- [ ] **Step 1: Write the failing test — create `frontend/tests/components/LoginForm.test.tsx`**
```typescript
import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import LoginPage from '@/app/(auth)/login/page'

vi.mock('@/lib/auth', () => ({
  useAuth: () => ({
    login: vi.fn().mockResolvedValue(undefined),
    loading: false,
    user: null,
  }),
}))

vi.mock('next/navigation', () => ({
  useRouter: () => ({ push: vi.fn() }),
}))

describe('LoginPage', () => {
  it('renders email and password fields', () => {
    render(<LoginPage />)
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument()
  })

  it('shows validation error when fields are empty', async () => {
    render(<LoginPage />)
    fireEvent.click(screen.getByRole('button', { name: /sign in/i }))
    await waitFor(() => {
      expect(screen.getByText(/email is required/i)).toBeInTheDocument()
    })
  })
})
```

- [ ] **Step 2: Run test to verify it fails**
```bash
cd frontend && npm run test:run -- tests/components/LoginForm.test.tsx
```
Expected: FAIL — component not found

- [ ] **Step 3: Replace `frontend/app/layout.tsx`**
```tsx
import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'
import { AuthProvider } from '@/lib/auth'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'AI Requirement Writer',
  description: 'Generate requirements from raw ideas',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className={inter.className}>
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  )
}
```

- [ ] **Step 4: Create `frontend/app/(auth)/layout.tsx`**
```tsx
export default function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="w-full max-w-md">{children}</div>
    </div>
  )
}
```

- [ ] **Step 5: Create `frontend/app/(auth)/login/page.tsx`**
```tsx
'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/lib/auth'

export default function LoginPage() {
  const { login } = useAuth()
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const newErrors: Record<string, string> = {}
    if (!email) newErrors.email = 'Email is required'
    if (!password) newErrors.password = 'Password is required'
    if (Object.keys(newErrors).length) { setErrors(newErrors); return }

    setSubmitting(true)
    try {
      await login(email, password)
      router.push('/dashboard')
    } catch (err) {
      setErrors({ general: err instanceof Error ? err.message : 'Login failed' })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="bg-white p-8 rounded-lg shadow">
      <h1 className="text-2xl font-semibold mb-6">Sign in</h1>
      {errors.general && <p className="text-red-600 text-sm mb-4">{errors.general}</p>}
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="email" className="block text-sm font-medium mb-1">Email</label>
          <input
            id="email" type="email" value={email}
            onChange={e => setEmail(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
          {errors.email && <p className="text-red-600 text-xs mt-1">{errors.email}</p>}
        </div>
        <div>
          <label htmlFor="password" className="block text-sm font-medium mb-1">Password</label>
          <input
            id="password" type="password" value={password}
            onChange={e => setPassword(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
          {errors.password && <p className="text-red-600 text-xs mt-1">{errors.password}</p>}
        </div>
        <button
          type="submit" disabled={submitting}
          className="w-full bg-blue-900 text-white rounded py-2 text-sm font-medium disabled:opacity-50"
        >
          {submitting ? 'Signing in...' : 'Sign in'}
        </button>
      </form>
      <p className="text-sm text-center mt-4">
        No account? <Link href="/register" className="text-blue-900 underline">Register</Link>
      </p>
    </div>
  )
}
```

- [ ] **Step 6: Create `frontend/app/(auth)/register/page.tsx`**
```tsx
'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { useAuth } from '@/lib/auth'

export default function RegisterPage() {
  const { register } = useAuth()
  const router = useRouter()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const newErrors: Record<string, string> = {}
    if (!name) newErrors.name = 'Name is required'
    if (!email) newErrors.email = 'Email is required'
    if (password.length < 8) newErrors.password = 'Password must be at least 8 characters'
    if (Object.keys(newErrors).length) { setErrors(newErrors); return }

    setSubmitting(true)
    try {
      await register(name, email, password)
      router.push('/dashboard')
    } catch (err) {
      setErrors({ general: err instanceof Error ? err.message : 'Registration failed' })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="bg-white p-8 rounded-lg shadow">
      <h1 className="text-2xl font-semibold mb-6">Create account</h1>
      {errors.general && <p className="text-red-600 text-sm mb-4">{errors.general}</p>}
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label htmlFor="name" className="block text-sm font-medium mb-1">Name</label>
          <input
            id="name" type="text" value={name}
            onChange={e => setName(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
          {errors.name && <p className="text-red-600 text-xs mt-1">{errors.name}</p>}
        </div>
        <div>
          <label htmlFor="email" className="block text-sm font-medium mb-1">Email</label>
          <input
            id="email" type="email" value={email}
            onChange={e => setEmail(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
          {errors.email && <p className="text-red-600 text-xs mt-1">{errors.email}</p>}
        </div>
        <div>
          <label htmlFor="password" className="block text-sm font-medium mb-1">Password</label>
          <input
            id="password" type="password" value={password}
            onChange={e => setPassword(e.target.value)}
            className="w-full border rounded px-3 py-2 text-sm"
          />
          {errors.password && <p className="text-red-600 text-xs mt-1">{errors.password}</p>}
        </div>
        <button
          type="submit" disabled={submitting}
          className="w-full bg-blue-900 text-white rounded py-2 text-sm font-medium disabled:opacity-50"
        >
          {submitting ? 'Creating account...' : 'Create account'}
        </button>
      </form>
      <p className="text-sm text-center mt-4">
        Have an account? <Link href="/login" className="text-blue-900 underline">Sign in</Link>
      </p>
    </div>
  )
}
```

- [ ] **Step 7: Run test to verify it passes**
```bash
npm run test:run -- tests/components/LoginForm.test.tsx
```
Expected: PASS — 2 tests

- [ ] **Step 8: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add frontend/app/layout.tsx \
        "frontend/app/(auth)/" \
        frontend/tests/components/LoginForm.test.tsx
git commit -m "feat: add login and register pages with client-side validation"
```

---

## Task 9: Project dashboard and new project form

**Files:**
- Create: `frontend/app/page.tsx`
- Create: `frontend/app/(dashboard)/layout.tsx`
- Create: `frontend/app/(dashboard)/dashboard/page.tsx`
- Create: `frontend/app/(dashboard)/projects/new/page.tsx`
- Create: `frontend/app/(dashboard)/projects/[id]/page.tsx`
- Create: `frontend/components/ProjectCard.tsx`
- Create: `frontend/tests/components/ProjectCard.test.tsx`

- [ ] **Step 1: Write the failing test — create `frontend/tests/components/ProjectCard.test.tsx`**
```typescript
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { ProjectCard } from '@/components/ProjectCard'

vi.mock('next/link', () => ({
  default: ({ href, children, className }: { href: string; children: React.ReactNode; className?: string }) => (
    <a href={href} className={className}>{children}</a>
  ),
}))

const project = {
  id: 1,
  name: 'My Web App',
  type: 'webapp',
  status: 'draft',
  mode: 'template',
  created_at: '2026-05-13T00:00:00Z',
}

describe('ProjectCard', () => {
  it('renders project name', () => {
    render(<ProjectCard project={project} />)
    expect(screen.getByText('My Web App')).toBeInTheDocument()
  })

  it('renders project type and status badge', () => {
    render(<ProjectCard project={project} />)
    expect(screen.getByText(/webapp/i)).toBeInTheDocument()
    expect(screen.getByText(/draft/i)).toBeInTheDocument()
  })
})
```

- [ ] **Step 2: Run test to verify it fails**
```bash
cd frontend && npm run test:run -- tests/components/ProjectCard.test.tsx
```
Expected: FAIL — module not found

- [ ] **Step 3: Create `frontend/components/ProjectCard.tsx`**
```tsx
import Link from 'next/link'

interface Project {
  id: number
  name: string
  type: string
  status: string
  mode: string
  created_at: string
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700',
  in_progress: 'bg-blue-100 text-blue-700',
  complete: 'bg-green-100 text-green-700',
}

export function ProjectCard({ project }: { project: Project }) {
  return (
    <Link
      href={`/projects/${project.id}`}
      className="block border rounded-lg p-4 hover:shadow-md transition-shadow bg-white"
    >
      <div className="flex items-start justify-between">
        <h3 className="font-medium text-gray-900">{project.name}</h3>
        <span className={`text-xs px-2 py-1 rounded-full font-medium ${statusColors[project.status] ?? 'bg-gray-100'}`}>
          {project.status.replace('_', ' ')}
        </span>
      </div>
      <p className="text-sm text-gray-500 mt-1 capitalize">{project.type}</p>
    </Link>
  )
}
```

- [ ] **Step 4: Run test to verify it passes**
```bash
npm run test:run -- tests/components/ProjectCard.test.tsx
```
Expected: PASS — 2 tests

- [ ] **Step 5: Create `frontend/app/page.tsx`**
```tsx
import { redirect } from 'next/navigation'

export default function Home() {
  redirect('/dashboard')
}
```

- [ ] **Step 6: Create `frontend/app/(dashboard)/layout.tsx`**
```tsx
'use client'

import { useAuth } from '@/lib/auth'
import { useRouter } from 'next/navigation'
import { useEffect } from 'react'

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const { user, loading, logout } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!loading && !user) router.push('/login')
  }, [user, loading, router])

  if (loading) return <div className="min-h-screen flex items-center justify-center text-gray-500">Loading…</div>
  if (!user) return null

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white border-b px-6 py-3 flex justify-between items-center">
        <span className="font-semibold text-gray-900">AI Requirement Writer</span>
        <div className="flex items-center gap-4">
          <span className="text-sm text-gray-600">{user.name}</span>
          <button onClick={logout} className="text-sm text-gray-500 hover:text-gray-900">Sign out</button>
        </div>
      </nav>
      <main className="max-w-5xl mx-auto px-6 py-8">{children}</main>
    </div>
  )
}
```

- [ ] **Step 7: Create `frontend/app/(dashboard)/dashboard/page.tsx`**
```tsx
'use client'

import { useEffect, useState } from 'react'
import Link from 'next/link'
import { apiClient } from '@/lib/api'
import { ProjectCard } from '@/components/ProjectCard'

interface Project {
  id: number
  name: string
  type: string
  status: string
  mode: string
  created_at: string
}

export default function DashboardPage() {
  const [projects, setProjects] = useState<Project[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    apiClient.get<{ data: Project[] }>('/projects')
      .then(res => setProjects(res.data))
      .catch(console.error)
      .finally(() => setLoading(false))
  }, [])

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-semibold">Projects</h1>
        <Link href="/projects/new" className="bg-blue-900 text-white text-sm px-4 py-2 rounded">
          New project
        </Link>
      </div>
      {loading && <p className="text-gray-500">Loading…</p>}
      {!loading && projects.length === 0 && (
        <p className="text-gray-500">No projects yet. Create your first one.</p>
      )}
      <div className="grid gap-4">
        {projects.map(p => <ProjectCard key={p.id} project={p} />)}
      </div>
    </div>
  )
}
```

- [ ] **Step 8: Create `frontend/app/(dashboard)/projects/new/page.tsx`**
```tsx
'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { apiClient } from '@/lib/api'

interface Template {
  id: number
  name: string
  type: string
}

export default function NewProjectPage() {
  const router = useRouter()
  const [templates, setTemplates] = useState<Template[]>([])
  const [name, setName] = useState('')
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    apiClient.get<{ data: Template[] }>('/templates')
      .then(res => setTemplates(res.data))
      .catch(console.error)
  }, [])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    if (!name || !selectedTemplate) { setError('Select a template and enter a name'); return }

    setSubmitting(true)
    try {
      const res = await apiClient.post<{ data: { id: number } }>('/projects', {
        name,
        type: selectedTemplate.type,
        template_id: selectedTemplate.id,
      })
      router.push(`/projects/${res.data.id}`)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create project')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="max-w-2xl">
      <h1 className="text-2xl font-semibold mb-6">New project</h1>
      {error && <p className="text-red-600 text-sm mb-4">{error}</p>}
      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label className="block text-sm font-medium mb-2">Project name</label>
          <input
            type="text" value={name} onChange={e => setName(e.target.value)}
            placeholder="e.g. Customer Portal"
            className="w-full border rounded px-3 py-2 text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-medium mb-2">Project type</label>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
            {templates.map(t => (
              <button
                key={t.id} type="button"
                onClick={() => setSelectedTemplate(t)}
                className={`border rounded-lg p-3 text-sm font-medium text-left transition-colors ${
                  selectedTemplate?.id === t.id
                    ? 'border-blue-900 bg-blue-50 text-blue-900'
                    : 'border-gray-200 hover:border-gray-400'
                }`}
              >
                {t.name}
              </button>
            ))}
          </div>
        </div>
        <button
          type="submit" disabled={submitting}
          className="bg-blue-900 text-white px-6 py-2 rounded text-sm font-medium disabled:opacity-50"
        >
          {submitting ? 'Creating…' : 'Create project'}
        </button>
      </form>
    </div>
  )
}
```

- [ ] **Step 9: Create `frontend/app/(dashboard)/projects/[id]/page.tsx`**
```tsx
export default function ProjectDetailPage({ params }: { params: { id: string } }) {
  return (
    <div>
      <h1 className="text-2xl font-semibold">Project #{params.id}</h1>
      <p className="text-gray-500 mt-2">AI generation features coming in Plan 2.</p>
    </div>
  )
}
```

- [ ] **Step 10: Run all frontend tests**
```bash
cd frontend && npm run test:run
```
Expected: All tests pass

- [ ] **Step 11: Commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add frontend/app/ frontend/components/ frontend/tests/
git commit -m "feat: add project dashboard, new project form, and ProjectCard component"
```

---

## Task 10: End-to-end smoke test

- [ ] **Step 1: Start the Laravel dev server**
```bash
cd backend && php artisan serve
```
Expected: Server running at http://localhost:8000

- [ ] **Step 2: Start the Next.js dev server (new terminal)**
```bash
cd frontend && npm run dev
```
Expected: Server running at http://localhost:3000

- [ ] **Step 3: Run full backend test suite**
```bash
cd backend && php artisan test
```
Expected: 12 tests, all green

- [ ] **Step 4: Run full frontend test suite**
```bash
cd frontend && npm run test:run
```
Expected: 7 tests, all green

- [ ] **Step 5: Manual smoke test — verify the golden path**
1. Open http://localhost:3000 — should redirect to `/login`
2. Click "Register" — fill in name, email, password — submit
3. Should land on `/dashboard` showing "No projects yet"
4. Click "New project" — should show template picker
5. Enter a project name, select "Web Application", click "Create project"
6. Should redirect to `/projects/1` showing the placeholder
7. Navigate back to `/dashboard` — project card should appear

- [ ] **Step 6: Final commit**
```bash
cd "/Users/edvin/Documents/Claude Sandbox 2"
git add .
git commit -m "chore: Plan 1 complete — foundation working, all tests passing"
```

---

## What's Next

| Plan | Covers | Prerequisite |
|------|--------|-------------|
| **Plan 2: Core AI Generation** | Dynamic intake form from template fields, Claude API 3-call streaming chain (BRD → stories → spec), inline review & approve UI, requirement draft versioning | Plan 1 |
| **Plan 3: Output Layer** | PDF/Word/Markdown export, JIRA/Confluence/Notion/GitHub connectors | Plan 2 |
| **Plan 4: Advanced Mode** | Conversational chat interface, discovery question loop, mode toggle | Plan 2 |
