<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function current_cycle(): string
{
    return (new DateTimeImmutable('first day of this month'))->format('Y-m');
}

function previous_cycle(string $cycle): string
{
    return DateTimeImmutable::createFromFormat('!Y-m', $cycle)
        ->modify('-1 month')
        ->format('Y-m');
}

function cycle_default_date(string $cycle): string
{
    $today = new DateTimeImmutable('today');

    if ($today->format('Y-m') === $cycle) {
        return $today->format('Y-m-d');
    }

    return DateTimeImmutable::createFromFormat('!Y-m', $cycle)
        ->modify('last day of this month')
        ->format('Y-m-d');
}

function valid_cycle(string $cycle): bool
{
    return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $cycle);
}

function cents_from_input(string $value): int
{
    $value = trim($value);
    $hasComma = str_contains($value, ',');
    $hasDot = str_contains($value, '.');

    if ($hasComma && $hasDot) {
        $normalized = str_replace('.', '', $value);
        $normalized = str_replace(',', '.', $normalized);
    } elseif ($hasComma) {
        $normalized = str_replace(',', '.', $value);
    } else {
        $normalized = $value;
    }

    if (!preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
        throw new InvalidArgumentException('El monto debe tener hasta dos decimales.');
    }

    return (int) round(((float) $normalized) * 100);
}

function money(int $cents): string
{
    $sign = $cents < 0 ? '-' : '';
    return $sign . '$' . number_format(abs($cents) / 100, 2, ',', '.');
}

function flash(?string $key = null, ?string $message = null): ?string
{
    if ($key !== null && $message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if ($key === null) {
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}
