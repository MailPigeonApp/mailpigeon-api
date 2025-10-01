# Contributing to MailPigeon API

Thank you for your interest in contributing to MailPigeon API! We welcome contributions from the community.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue with:
- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior vs actual behavior
- Your environment (PHP version, database version, OS)
- Any relevant logs or error messages

### Suggesting Features

We welcome feature suggestions! Please create an issue with:
- A clear description of the feature
- Use cases and benefits
- Any implementation ideas you may have

### Pull Requests

1. **Fork the repository** and create your branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**:
   - Write clear, concise code
   - Follow the existing code style
   - Keep changes focused on a single feature or fix

3. **Test your changes**:
   - Ensure your code works with the existing functionality
   - Test with a local database setup
   - Verify API endpoints function as expected

4. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: add your feature description"
   ```

   Use conventional commit messages:
   - `feat:` - New feature
   - `fix:` - Bug fix
   - `docs:` - Documentation changes
   - `refactor:` - Code refactoring
   - `chore:` - Maintenance tasks

5. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

6. **Open a Pull Request** with:
   - A clear title and description
   - Reference to any related issues
   - Screenshots or examples if applicable

## Development Setup

1. Clone your fork:
   ```bash
   git clone https://github.com/your-username/mailpigeon-api.git
   cd mailpigeon-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure your local environment:
   - Create a `.env` file with your database credentials
   - Update `config/db.php` to use local dotenv loading (uncomment lines 10-12)

4. Set up a local PostgreSQL database with the required schema (see README.md)

## Code Style

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Add comments for complex logic
- Keep functions focused and concise

## Integration Development

When adding new integrations:

1. Follow the existing pattern in the `/api/v1/submit` endpoint
2. Check for the integration type in `active_integrations` array
3. Retrieve configuration from the `integrations` table
4. Handle errors gracefully with appropriate error messages
5. Update documentation in README.md

## Database Changes

If your contribution requires database schema changes:

1. Document the changes clearly in your PR
2. Provide migration scripts if possible
3. Update the database schema section in README.md
4. Consider backwards compatibility

## Questions?

Feel free to open an issue for any questions about contributing. We're here to help!

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards other community members

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
