<!DOCTYPE html>
<html
    lang="id"
    class="h-full"
>

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- CSRF Token — wajib ada untuk semua form Laravel --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>{{ config('app.name', 'SISKO') }} — @yield('title', 'Masuk')</title>

    {{-- Vite: compile CSS (Tailwind 4) dan JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Flux UI Pro styles --}}
    {{-- @fluxAppearance --}}
</head>

<body class="h-full antialiased">

    {{--
        Layout ini dipakai untuk halaman auth (login, forgot password, dll).
        Tidak ada sidebar atau navigasi — hanya konten form di tengah layar.

        Konten halaman dirender di sini via $slot (Livewire #[Layout] attribute).
    --}}
    {{ $slot }}

    {{-- Flux UI Pro scripts --}}
    @fluxScripts

    {{-- Livewire scripts (sudah include di fluxScripts, tapi eksplisit agar jelas) --}}
    @livewireScripts

</body>

</html>
