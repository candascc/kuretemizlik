# Contributing Guidelines

Thank you for contributing to this project! This document outlines the process for contributing.

## Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow

## Getting Started

1. Read [Developer Onboarding Guide](docs/DEVELOPER_ONBOARDING.md)
2. Set up your development environment
3. Pick an issue or create a new one
4. Create a feature branch

## Development Process

### 1. Create Branch

```bash
git checkout -b feature/my-feature
```

### 2. Make Changes

- Write code following our standards
- Write tests for new features
- Update documentation

### 3. Test Your Changes

```bash
composer ci-check
```

### 4. Commit Changes

Use clear, descriptive commit messages:

```
feat: Add payment processing feature

- Implement PaymentService
- Add payment validation
- Add payment tests
```

### 5. Push and Create Pull Request

Push your branch and create a pull request.

## Code Style Requirements

### PHP

- PSR-12 coding standard
- Type hints for all parameters and return types
- PHPDoc for public methods
- No magic numbers (use constants)

### Testing

- 90%+ coverage for critical paths
- 80%+ coverage for general code
- All tests must pass
- New features require tests

### Documentation

- Update README if needed
- Document new APIs
- Add examples for complex features

## Pull Request Requirements

### Before Submitting

- [ ] All tests pass
- [ ] Code style check passes
- [ ] PHPStan analysis passes
- [ ] Documentation updated
- [ ] No merge conflicts

### PR Description

Include:
- What changes were made
- Why changes were made
- How to test
- Screenshots (if UI changes)

### Review Process

1. Automated checks must pass
2. Code review by maintainers
3. Address feedback
4. Merge when approved

## Testing Requirements

### Unit Tests

- Test individual methods
- Mock dependencies
- Test edge cases
- Test error conditions

### Integration Tests

- Test component interactions
- Use real database (SQLite)
- Test API endpoints
- Test authentication/authorization

### Functional Tests

- Test end-to-end flows
- Test user interactions
- Test error handling

## Code Quality

### PHPStan

- Level 5 minimum
- Fix all errors
- Use baseline for legacy code

### PHP-CS-Fixer

- All files must pass
- Auto-fix before committing

### Test Coverage

- Critical paths: 90%+
- General code: 80%+
- New code: 100%

## Commit Message Format

```
<type>: <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style
- `refactor`: Code refactoring
- `test`: Tests
- `chore`: Maintenance

### Examples

```
feat: Add user authentication

Implement login/logout functionality with session management.

Closes #123
```

```
fix: Resolve payment processing error

Fix issue where payments over 1000 were rejected incorrectly.

Fixes #456
```

## Branch Naming

- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation
- `refactor/` - Refactoring
- `test/` - Tests

## Release Process

1. All tests pass
2. Code review approved
3. Documentation updated
4. Version bumped
5. Changelog updated
6. Tagged and released

## Questions?

- Check [Troubleshooting Guide](docs/TROUBLESHOOTING.md)
- Ask in team chat
- Create an issue

Thank you for contributing! 🎉




