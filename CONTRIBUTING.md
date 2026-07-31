# Contributing to AeroBook

Thank you for considering contributing to AeroBook! We welcome contributions of all kinds — bug fixes, new features, documentation improvements, and more.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork**:
   ```bash
   git clone https://github.com/your-username/airline-reservation-system.git
   ```
3. **Set up the development environment**:
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   mysql -u root -p aerobook_db < database/aerobook.sql
   ```
4. **Create a branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Development Process

### Coding Standards

- **PHP**: Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style
- **SQL**: Use prepared statements for ALL queries — never concatenate user input
- **HTML/CSS**: Use existing Bootstrap 5 patterns; prefer CSS variables over hardcoded values
- **JavaScript**: Vanilla JS only; avoid jQuery or other dependencies

### What to Work On

Check the [open issues](https://github.com/suman2308/airline-reservation-system/issues) for:

- **Bug fixes** — Look for issues labeled `bug`
- **Features** — Look for issues labeled `enhancement`
- **Documentation** — Look for issues labeled `documentation`
- **Good first issue** — Beginner-friendly tasks

### Making Changes

1. **Keep changes focused** — One feature/bug per pull request
2. **Follow existing patterns** — Look at how similar features are implemented
3. **Use prepared statements** — Never use `mysqli_query()` with concatenated SQL
4. **Add validation** — All form inputs need server-side validation
5. **CSRF protection** — All POST forms need a CSRF token
6. **Output escaping** — Use `htmlspecialchars()` for all dynamic output
7. **Test manually** — Verify the workflow works end-to-end

### Commit Messages

Use clear, descriptive commit messages:

```
feat: add seat map legend for exit rows
fix: correct booking confirmation email link
docs: update installation guide for Docker
refactor: extract seat map generation into helper
```

### Pull Request Process

1. **Update documentation** if you add or change functionality
2. **Test your changes** — verify the workflow works
3. **Create a pull request** with a clear description of what you changed and why
4. **Respond to feedback** — a maintainer may request changes
5. **Keep it small** — PRs under 200 lines are much easier to review

## Architecture Notes

- **`includes/helpers.php`** — Contains reusable database query functions. Add new queries here rather than inline in pages.
- **`includes/functions.php`** — Contains utility functions (formatting, CSRF, session). Keep it free of database logic.
- **`includes/Validation.php`** — All server-side validation functions. One function per form type.
- **`includes/Security.php`** — Rate limiting, session hardening, security headers.
- **Integration classes** — `AeroMailer`, `AeroQR`, `AeroPDF`, `AeroICS`, `AeroNotifications`, `AeroUpload` — each in its own file.

## Database Changes

If you need to modify the database schema:

1. Update `database/aerobook.sql` with the new table/column/constraint
2. **Don't remove backward compatibility** — use `ALTER TABLE` for existing schema changes
3. Add appropriate indexes for new query patterns
4. Document indexes with comments explaining which query pattern they accelerate

## Testing

Currently, AeroBook doesn't have an automated test suite. To test your changes:

1. Set up a local development environment
2. Walk through the complete workflow (registration → search → book → confirm)
3. Test edge cases (empty results, invalid input, concurrent booking)
4. Check the admin Operations Center for any regression

## Adding a New Page

1. Create the page file in the appropriate directory (root for public, `admin/` for admin)
2. Include the appropriate header:
   - Public: `require_once 'includes/header.php'`
   - Admin: `require_once __DIR__ . '/includes/admin-header.php'`
3. Use existing helper functions from `includes/helpers.php`
4. Add server-side validation via `includes/Validation.php`
5. Use CSRF token on all POST forms: `<?php csrfField(); ?>`
6. Escape all output: `<?php echo htmlspecialchars($var); ?>`
7. Include the footer: `require_once 'includes/footer.php'`

## Need Help?

If you have questions, please open an issue with the `question` label.
