<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Dilempar saat harga dari CoinGecko tidak bisa diambil (timeout, rate limit, 5xx).
 */
class PriceUnavailableException extends RuntimeException {}
