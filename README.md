# AuctionHouse

A modern, real-time auction platform built with **Laravel**, **Vue 3**, **Inertia.js**, and **Laravel Reverb**.

![AuctionHouse](/screenshots/home.png)

## Features

-   **Real-time Bidding**: Instant updates for bids and current prices using Laravel Reverb (WebSocket).
-   **Magic Link Authentication**: Passwordless login flow with email verification.
-   **Auction Management**: Create, view, and manage auctions with image uploads.
-   **Search & Filtering**: Find auctions by category or keyword.
-   **User Dashboard**: Track your created auctions and winning bids.
-   **Internationalization**: Multi-language support (English & Polish).
-   **Dark Mode**: Native dark mode support.
-   **Responsive Design**: Mobile-friendly interface.

## Tech Stack

-   **Backend**: Laravel 11
-   **Frontend**: Vue 3, Inertia.js, TypeScript
-   **Styling**: Tailwind CSS
-   **State Management**: Pinia
-   **Broadcasting**: Laravel Reverb
-   **Database**: SQLite (default) / MySQL

## Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/yourusername/auctionhouse.git
    cd auctionhouse
    ```

2.  **Install PHP dependencies**
    ```bash
    composer install
    ```

3.  **Install Node.js dependencies**
    ```bash
    npm install
    ```

4.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    Configure your database in `.env`.

5.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

6.  **Start Development Servers**

    You need to run these commands in separate terminal windows:

    *   **Laravel Server**:
        ```bash
        php artisan serve
        ```
    *   **Vite (Frontend)**:
        ```bash
        npm run dev
        ```
    *   **Reverb (WebSockets)**:
        ```bash
        php artisan reverb:start
        ```
    *   **Queue Worker (Optional, for emails)**:
        ```bash
        php artisan queue:listen
        ```

## Development

**Local Login Bypass**:
In `local` environment, the Mailer is set to `log` by default. When requesting a Magic Link, you will be immediately redirected to the dashboard for convenience. To test actual emails, change `App::environment()` checks or use a tool like Mailpit.

## License

The content of this repository is licensed under the [MIT license](https://opensource.org/licenses/MIT).
