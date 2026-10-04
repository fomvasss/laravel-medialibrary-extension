# Changelog Laravel Metadialibrary extension

## 6.5.1 - 2026-10-04

### Fixed

- `strict_refresh`: an anonymous temporary upload is bound to the session only when the request came with the session cookie. A session started for a request without cookies (e.g. Sanctum `EnsureFrontendRequestsAreStateful` with a Bearer client) is new on every request, so in 6.5.0 such uploads could not be attached by the next request

## 6.5.0 - 2026-10-04

### Security

- `strict_refresh`: `mediaManageRefresh()` attaches only own temporary uploads. An upload with `user_id` — only by the same user (the `$user` argument or the authenticated one); an anonymous upload made in a request with a session — only from the same session (`UploadMediaTemporaryFile` stores a session hash in the `temporary_session` custom property, removed on attach). Uploads without both (stateless API) behave as before. Previously any temporary upload was accepted by its `id`

## 6.4.1 - 2026-10-04

### Fixed

- `mediaManage()`: the expand format (`files[N][id|file]`, `weight`, `delete`, custom properties) was silently ignored for a collection named like a Symfony `Request` property — `files`, `query`, `request`, `headers` and others. Collection data is now read from the request fields and files

## 6.4.0 - 2026-09-23

### Added

- `strict_refresh` config option (default `false`). When enabled, `mediaManageRefresh()` attaches only media of the model or temporary uploads, deletes (`delete`, `media_deleted`) only media of the model and ignores `user_id` from the data. Other ids are skipped without an error

### Security

- `mediaManageRefresh()` trusts every media `id` it receives: with client (API) data anyone can attach someone else's media to their model or delete any media. Enable `strict_refresh` in projects that pass request data to it

### Fixed

- `mediaManageRefresh()`: attaching by an invalid id no longer fails on PostgreSQL for a uuid media key — the item is skipped (in strict mode this also applies to deletion)

## 6.3.2 - 2026-06-15

### Fixed

- Added missing `use_auth_user` key to config file (defaults to `false`)

## 6.0.0 - 2024-06-05

- Support Laravel 10+
- Require Laravel Media Library 11
- Unsupported Manipulation class (Spatie)
- Add static method `ClearMediaTemporary::doHandle()`

## 5.0.0

- Add temparary media
- Add actions: upload, delete, clear
- Add support save from base64
- Remove change config in DB primary key

## 4.3.0 - 2021-08-11

- Add sync - Media with new Model
- Refactor

## 4.0.0 - 2021-01-24

- Support Laravel 8
- Refactor
- Add new methods & change API
- Remove validation

## 3.0.0 - 2020-06-15

- Support laravel-medialibrary v.8

## 2.0.0 - 2020-03-26

- Support Laravel 7

## 1.0.0 - 2019-11-25

- Release v1
