# Changelog

## Unreleased

- Initialize the HappyRO GM repository with Laravel 13 and Ant Design Pro 6.0.3.
- Add native systemd services for the local development deployment.
- Use a dedicated database on the existing HappyRO MariaDB instance.
- Replace the frontend login mock with Laravel Session and Sanctum authentication.
- Add injectable authentication, rate limiting, authorization, and audit services.
- Add role and permission tables without GM-specific table prefixes.
- Add the `gm:user:create` command for provisioning management accounts.
- Restore the responsive Ant Design Pro login layout without alternate sign-in methods.
- Remove unbacked demo routes that repeatedly displayed API errors after login.
- Rename the application to HappyRO and vertically center the login form.
- Use local Ragnarok artwork for the login logo and a cache-busted browser icon.
- Remove the Ant Design Pro metadata footer from the application.
