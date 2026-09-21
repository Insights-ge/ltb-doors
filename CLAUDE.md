<laravel-boost-guidelines>
=== .ai/tamari rules ===

## Naming Conventions

### Variables & Collections

- Variables: camelCase, descriptive — `$profilePicture`, not `$pic`.
- Collections: plural camelCase — `$users`, `$activeOrders`.

### Classes

- Models: singular PascalCase — `User`, `BlogPost`.
- Controllers: singular PascalCase with `Controller` suffix — `PostController`, `UserController`.
- Form Requests: PascalCase, `ActionModelRequest` format — `StorePostRequest`, `UpdateUserRequest`.

### Methods & Routes

- Methods: camelCase — `updateTaskPosition`, `getUserProfile`.
- Route URIs: kebab-case, plural — `/active-users`, `/blog-posts`.
- Named routes: dot notation `resource.action` — `users.show_active`, `posts.index`.

### Database

- Tables: snake_case plural — `posts`, `user_profiles`.
- Pivot tables: snake_case, singular model names in alphabetical order — `post_user`, `category_post`.
- `hasOne`/`belongsTo` relationships: singular camelCase — `author()`, `category()`.
- All other relationships: plural camelCase — `comments()`, `tags()`.

## Controllers

### CRUD Method Names

Use standard CRUD method names. For non-CRUD operations use descriptive names (`login`, `register`, `logout`).

| Method | HTTP | URI |
|---|---|---|
| `index` | GET | `/posts` |
| `show` | GET | `/posts/{post}` |
| `create` | GET | `/posts/create` |
| `store` | POST | `/posts` |
| `edit` | GET | `/posts/{post}/edit` |
| `update` | PUT | `/posts/{post}` |
| `destroy` | DELETE | `/posts/{post}` |

### HTTP Status Codes

| Code | Meaning |
|---|---|
| 200 | Successful, returns data |
| 201 | Resource created |
| 204 | Successful, no content |
| 401 | Unauthenticated |
| 403 | Authenticated but unauthorized |
| 404 | Not found |
| 422 | Validation failure |

## Form Requests

- Name as `ActionModelRequest` — e.g., `StorePostRequest`, `UpdateUserRequest`.
- One `FormRequest` per controller method.
- When many `FormRequest` classes serve one model, group them in a subdirectory (e.g., `Requests/Post/`).

## Blade Templates

- Never write database queries or model calls directly in Blade templates. All data must be passed from the controller.

## Development Practices

### Filament: Resources Must Stay Thin

Never put business logic in `Resource`, `Page`, `Widget`, `RelationManager`, or any form/table closure callback. Closures are for UI concerns only — delegate everything else to an Action class.

```php
// Bad — business logic inside a closure
Action::make('approve')
    ->action(function (Order $record) {
        $record->approve();
        Mail::to($record->customer)->send(...);
    });

// Good
Action::make('approve')
    ->action(fn (Order $record) => app(ApproveOrderAction::class)->execute($record));
```

### Filament: No Repeated Closures

If the same closure logic appears more than once (e.g., `->visible(fn () => auth()->user()?->isAdmin())`), extract it into a `Policy`, a named method, or a service call.

### Filament: Modular Form Schemas

Form schemas longer than ~50 lines must be split into dedicated section classes.

```php
// Bad: 300-line inline schema

// Good
return $form->schema([
    GeneralInformationSection::schema(),
    PricingSection::schema(),
    InventorySection::schema(),
]);
```

### Filament: Eager Load All Relationship Columns

Any column that traverses a relationship must be eager-loaded in `getEloquentQuery()`. Never rely on lazy loading in tables.

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->with(['customer']);
}
```

### One Action = One Use Case

Each Action represents a single, named business operation. Avoid generic catch-all methods.

- `PublishProductAction`, `ArchiveProductAction`, `DuplicateProductAction` — correct
- `ProductService::updateEverything()` — wrong

### DTOs Across All Layer Boundaries

Never pass raw arrays between layers. Always convert to a DTO at the boundary.

```php
// Bad
$service->create($request->validated());

// Good
$service->create(ProductData::from($request->validated()));
```

### No Static Service Calls

Use `app(ServiceClass::class)` or constructor injection. Never call services statically — static calls make unit testing impossible.

```php
// Bad
ProductService::sync();

// Good
app(ProductService::class)->sync();
```

### Method Length

Methods should rarely exceed 30–40 lines. If a method grows larger, extract private helper methods, a new Action, or a dedicated Service.

### No Boolean Arguments

Boolean parameters hide intent. Replace with descriptively named methods or enum values.

```php
// Bad — nobody knows what `true` means
sendEmail($user, true);

// Good
sendVerificationEmail($user);
sendEmail($user, EmailType::Verification);
```

### Explicit Collection Generic Types

Always annotate the item type of returned collections so Larastan can infer types correctly.

```php
/** @return Collection<int, Product> */
public function products(): Collection
```

### Domain Events for Side Effects

Actions must not chain multiple side effects inline. Dispatch a domain event and handle each side effect in a separate Listener. This keeps Actions small and side effects independently testable.

```
// Bad: CreateOrderAction directly calls SendEmail + CreateInvoice + NotifyAdmin

// Good: CreateOrderAction dispatches OrderCreated
//   Listeners: SendOrderEmailListener, CreateInvoiceListener, NotifyAdminListener
```

### No Magic Strings

Never compare against raw string literals for statuses, types, providers, roles, permissions, or feature flags. Enums are mandatory for all of these.

```php
// Bad
if ($status === 'approved')

// Good
if ($status === OrderStatus::Approved)
```

### Action Test Coverage

Every Action must have four tests:

1. **Success** — happy path, assert expected state change.
2. **Validation** — assert invalid input is rejected.
3. **Authorization** — assert unauthorized users cannot execute.
4. **Tenant boundary** — assert the Action cannot access another workspace's data.

### Architectural Dependency Direction

Dependencies must only flow in one direction:

```
Filament → Action → Service → Model
```

These directions are forbidden:

- `Service` → Filament
- `Model` → Filament
- `Action` → Resource
- Domain → Integration (use contracts only)

## Filament

Use `search-docs` before making Filament changes. Always use Filament-specific Artisan commands to create files — run `php artisan list` to discover them.

### Correct Namespaces

| Component | Namespace |
|---|---|
| Form fields (`TextInput`, `Select`, `Repeater`, etc.) | `Filament\Forms\Components\` |
| Infolist entries (`TextEntry`, `IconEntry`, etc.) | `Filament\Infolists\Components\` |
| Layout (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.) | `Filament\Schemas\Components\` |
| Schema utilities (`Get`, `Set`) | `Filament\Schemas\Components\Utilities\` |
| Table columns (`TextColumn`, `IconColumn`, etc.) | `Filament\Tables\Columns\` |
| Table filters (`SelectFilter`, `Filter`, etc.) | `Filament\Tables\Filters\` |
| Actions (`DeleteAction`, `CreateAction`, etc.) | `Filament\Actions\` — **never** a sub-namespace |
| Icons | `Filament\Support\Icons\Heroicon` (enum, not string) |

### Patterns

Use `Get $get` to read other form field values for conditional logic:

```php
Select::make('type')
    ->options(ContactType::class)
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),
```

Use `Set $set` inside `->afterStateUpdated()`. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke requests:

```php
TextInput::make('name')
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

TextInput::make('slug')->required(),
```

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

```php
Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')->columnSpan(1),
            TextInput::make('last_name')->columnSpan(1),
            TextInput::make('bio')->columnSpanFull(),
        ]),
    ]),
```

Use `Repeater` for inline `HasMany` management. `->relationship()` with no arguments binds to the relationship matching the field name:

```php
Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')->required(),
        TextInput::make('qualification')->required(),
    ])
    ->columns(2),
```

Use `->state()` with a closure to compute derived column values:

```php
TextColumn::make('full_name')
    ->state(fn (Contact $record): string => "{$record->first_name} {$record->last_name}"),
```

### Testing

Always call `$this->actingAs(User::factory()->create())` before testing panel functionality. Initialize tenancy when testing tenant-scoped pages.

```php
// Table test
livewire(ListContacts::class)
    ->assertCanSeeTableRecords($contacts)
    ->searchTable($contacts->first()->email)
    ->assertCanSeeTableRecords($contacts->take(1))
    ->assertCanNotSeeTableRecords($contacts->skip(1));
```

```php
// Create resource — ends with assertRedirect()
livewire(CreateContact::class)
    ->fillForm(['email' => 'jane@example.com', 'status' => 'subscribed'])
    ->call('create')
    ->assertHasNoFormErrors()
    ->assertNotified()
    ->assertRedirect();

assertDatabaseHas(Contact::class, ['email' => 'jane@example.com']);
```

```php
// Edit resource — use call('save'), not call('create'); no assertRedirect()
livewire(EditContact::class, ['record' => $contact->id])
    ->fillForm(['email' => 'updated@example.com'])
    ->call('save')
    ->assertHasNoFormErrors()
    ->assertNotified();

assertDatabaseHas(Contact::class, ['id' => $contact->id, 'email' => 'updated@example.com']);
```

```php
// Page action test
use Filament\Actions\Testing\TestAction;

livewire(ListContacts::class)
    ->callAction(TestAction::make('addField'), [
        'name' => 'Birthday', 'key' => 'birthday', 'type' => 'date',
    ])
    ->assertNotified();
```

```php
// Table row action test
livewire(ListContacts::class)
    ->callAction(TestAction::make('approve')->table($contact))
    ->assertNotified();
```

```php
// Validation test
livewire(CreateContact::class)
    ->fillForm(['email' => 'not-an-email'])
    ->call('create')
    ->assertHasFormErrors(['email' => 'email'])
    ->assertNotNotified();
```

### Common Mistakes

- **`$navigationIcon`** must be typed `string | BackedEnum | null`, not `?string` — Livewire will throw a type error at runtime.
- **Never use `->dehydrated(false)`** on a field that needs to be saved. It silently strips the value before the save handler runs. Only use it for UI-only helper fields.
- **`Grid`, `Section`, `Fieldset`, and `Repeater` do not span full width by default.** Always add `->columnSpanFull()` explicitly when needed.
- **Use `Select::make('relation_id')->relationship('relation', 'name')`** for BelongsTo fields. `BelongsToSelect` does not exist in v5.
- **Never import actions from `Filament\Tables\Actions\` or `Filament\Forms\Actions\`.** Always use `Filament\Actions\`.

## Tinker

Always use **single quotes** for the outer shell argument to prevent shell variable expansion:

```bash
php artisan tinker --execute 'Contact::query()->count();'
```

Use double quotes for PHP strings **inside**:

```bash
php artisan tinker --execute 'Contact::where("status", "subscribed")->count();'
```

## Artisan Tips

Filter `route:list` output with: `--method=GET`, `--name=contacts`, `--path=api`, `--except-vendor`.

Read config values with dot notation: `php artisan config:show database.default`, `php artisan config:show app.name`.

## Pest Notes

When creating tests, do **not** include the test suite directory in the `{name}` argument:

```bash
# Correct
php artisan make:test --pest CreateContactActionTest

# Wrong — Pest will create a nested directory
php artisan make:test --pest Feature/CreateContactActionTest
```

## Deployment

Laravel applications can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

=== laraveldaily/filacheck/core rules ===

## laraveldaily/filacheck

- After you have created/modified any files in `app/Filament` folder, you must run `vendor/bin/filacheck --fix`, to ensure there is no deprecated Filament code. Reported not fixed issues MUST be fixed before continuing.

=== tightenco/duster/core rules ===

## Duster Code Formatter

- You must run `vendor/bin/duster fix --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Duster wraps Laravel Pint and other formatters, so never run Pint directly. Always prefer Duster for formatting tasks.

</laravel-boost-guidelines>

## Project Architecture

This is a **multi-tenant modular SaaS platform**. Full documentation is in `docs/`. Key rules that must always be followed:

### Domain Structure

All business logic lives in `app/Domains/`. Never put logic in controllers, Filament resources, or random service classes.

```
app/Domains/Core/          ← tenancy, users, memberships, plans, feature flags
app/Domains/Communications/ ← contacts, email marketing
app/Integrations/Email/    ← provider adapters (Brevo, SMTP) — never imported from Domains
```

### Multi-Tenancy (Critical)

- Every tenant-owned model **must** have a `workspace_id` column, foreign key to `workspaces`, and use the `BelongsToWorkspace` trait.
- Services and Actions **must** receive `Workspace` as an explicit parameter — never resolve from session inside an Action or Service.
- Jobs touching tenant data **must** use the `HasTenantContext` trait to serialize/restore `workspace_id`.
- Filament resources **must** scope all queries to the current workspace via `getEloquentQuery()` or panel-level tenancy.
- Never query tenant-owned models without a workspace scope.

### Actions, DTOs, Services

- **Actions** — application orchestration: check feature flag, validate, call service, dispatch job. Thin.
- **Services** — pure business logic. No HTTP, no queues, no Filament.
- **DTOs** — all properties `readonly`. Immutable after construction. Named `NounData` (e.g. `ContactData`).
- Filament resources call Actions. Actions call Services. Nothing calls Brevo or SMTP directly.

### Provider Isolation

- `Integrations/Email/Brevo/` and `Integrations/Email/Smtp/` are the **only** places with provider-specific code.
- `SendMessageData` and `ProviderSendResult` are provider-agnostic DTOs — never add provider-specific fields.
- All providers implement `EmailProviderContract`. Business logic only knows the contract.

### Naming

| Type | Pattern | Example |
|---|---|---|
| Action | `VerbNounAction` | `DispatchCampaignAction` |
| Service | `NounService` | `EmailDeliveryService` |
| DTO | `NounData` | `ContactData` |
| Job | `VerbNounJob` | `SendCampaignBatchJob` |
| Contract | `NounContract` | `EmailProviderContract` |

### Feature Flags

- Use `FeatureFlagService::check(Feature::EmailMarketing, $workspace)` at the top of any gated Action.
- Feature enum cases: `EmailMarketing`, `Sms` (reserved), `Booking` (reserved).

### Testing

- Every Action that reads/writes tenant data needs a tenant boundary test asserting it cannot access another workspace's data.
- Provider adapter tests use `Http::fake()` — no live API calls in tests.
- Run `php artisan test --compact` to verify.

### What Not to Do

- Do not build SMS or Booking modules yet — architecture must stay ready for them.
- Do not use `DB::` — use `Model::query()`.
- Do not put business logic in Filament resources.
- Do not import from `Integrations/` anywhere outside `Integrations/`.
- Do not use `env()` outside config files.
