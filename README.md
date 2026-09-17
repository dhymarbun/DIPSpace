# DIPSpace — Facility Reservation Demo

A minimal procedural PHP and MySQL proof of concept.

## Create the database

Import `database.sql` into MySQL. The import resets the demo tables, creates sample facilities and users, and creates the `ppk_demo` database if needed.

```sh
mysql -u root < database.sql
```

## Run locally

From this project root, run:

```sh
php -S localhost:8000
```

Open `http://localhost:8000` in your browser.

## Demo accounts

Both accounts use password `password123`:

- Pengguna: `budi@dipspace.test`
- Admin: `admin@dipspace.test`

## Demo flows

1. View facilities without logging in.
2. Log in using either sample account.
3. Submit a facility reservation and see it listed under **Reservasi Saya**.

## Default connection settings

`config/db.php` uses MySQL host `localhost`, user `root`, an empty password, and database `ppk_demo`.
