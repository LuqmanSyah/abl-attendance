# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel 12 attendance application using Filament 5 for the admin UI.

- `app/Models/` contains Eloquent models such as `Employee`, `Position`, and attendance records.
- `app/Filament/Resources/` contains Filament CRUD resources, split into `Schemas`, `Tables`, and `Pages`.
- `database/migrations/` defines schema changes; `database/seeders/` contains initial master data.
- `resources/css/` and `resources/js/` are Vite-managed frontend assets.
- `tests/Feature/` and `tests/Unit/` contain PHPUnit tests.
- `routes/web.php` and `routes/console.php` hold route and console definitions.

## Build, Test, and Development Commands

- `composer install` installs PHP dependencies.
- `npm install` installs frontend tooling.
- `composer run dev` starts the Laravel server, queue listener, log tailing, and Vite together.
- `php artisan serve` starts only the Laravel HTTP server.
- `npm run dev` starts Vite in development mode.
- `npm run build` builds production frontend assets.
- `php artisan migrate --seed` applies migrations and seeders.
- `composer test` or `php artisan test` runs the PHPUnit test suite.
- `./vendor/bin/pint` formats PHP files with Laravel Pint.

## Coding Style & Naming Conventions

Follow Laravel conventions and PSR-12 formatting. Use 4-space indentation for PHP. Keep classes in namespaces matching their paths, for example `App\Filament\Resources\Employees\Schemas\EmployeeForm`.

Use singular model names (`Employee`, `Position`) and plural database tables (`employees`, `positions`). Prefer descriptive Filament component labels in Indonesian to match the current UI, for example `->label('Nama Pegawai')`.

## Testing Guidelines

Use PHPUnit through Laravel’s test runner. Put HTTP and Filament workflow coverage in `tests/Feature/`; put isolated logic tests in `tests/Unit/`. Name test files by behavior, such as `EmployeeSuperiorTest.php`, and keep assertions focused on one outcome per test.

Run `php artisan test` before submitting changes. For database-related features, include migration and seeder behavior in the verification path.

## Migration Policy

This project is still in development. When changing table structure, edit the original table migration directly instead of adding `add_*_to_*_table` migrations. Keep seeders aligned with any new required columns or defaults.

## Commit & Pull Request Guidelines

Recent commits use Conventional Commit prefixes such as `feat:`, `refactor:`, and `fix:`. Keep messages imperative and scoped, for example `feat: add superior rules to positions`.

Pull requests should include a short summary, testing performed, database changes or migrations, and screenshots for visible Filament UI changes. Link related issues or task notes when available.

## Security & Configuration Tips

Do not commit `.env` or credentials. The project uses MySQL through environment configuration; verify `DB_HOST`, `DB_PORT`, and database credentials before running migrations. Keep generated build artifacts and dependency directories out of commits unless explicitly required.
