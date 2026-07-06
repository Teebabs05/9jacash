<?php
/**
 * Global application constants shared across every module.
 * Keep this file free of any environment-specific values.
 */

declare(strict_types=1);

// ---------------------------------------------------------------
// User account status
// ---------------------------------------------------------------
const USER_STATUS_ACTIVE    = 'active';
const USER_STATUS_SUSPENDED = 'suspended';
const USER_STATUS_BANNED    = 'banned';

// ---------------------------------------------------------------
// KYC status
// ---------------------------------------------------------------
const KYC_STATUS_UNVERIFIED = 'unverified';
const KYC_STATUS_PENDING    = 'pending';
const KYC_STATUS_APPROVED   = 'approved';
const KYC_STATUS_REJECTED   = 'rejected';

// ---------------------------------------------------------------
// Wallet types
// ---------------------------------------------------------------
const WALLET_MAIN      = 'main';
const WALLET_BONUS     = 'bonus';
const WALLET_REFERRAL  = 'referral';
const WALLET_MINING    = 'mining';
const WALLET_PENDING   = 'pending';

const WALLET_TYPES = [
    WALLET_MAIN,
    WALLET_BONUS,
    WALLET_REFERRAL,
    WALLET_MINING,
    WALLET_PENDING,
];

// ---------------------------------------------------------------
// Ledger entry direction / source
// ---------------------------------------------------------------
const LEDGER_CREDIT = 'credit';
const LEDGER_DEBIT  = 'debit';

const LEDGER_SOURCE_DEPOSIT           = 'deposit';
const LEDGER_SOURCE_WITHDRAWAL        = 'withdrawal';
const LEDGER_SOURCE_MINING            = 'mining';
const LEDGER_SOURCE_TASK              = 'task';
const LEDGER_SOURCE_AD                = 'ad';
const LEDGER_SOURCE_SPIN              = 'spin';
const LEDGER_SOURCE_CHECKIN           = 'checkin';
const LEDGER_SOURCE_REFERRAL          = 'referral';
const LEDGER_SOURCE_ADMIN_ADJUSTMENT  = 'admin_adjustment';
const LEDGER_SOURCE_TRANSFER          = 'transfer';

// ---------------------------------------------------------------
// Generic request/approval statuses
// ---------------------------------------------------------------
const STATUS_PENDING  = 'pending';
const STATUS_APPROVED = 'approved';
const STATUS_REJECTED = 'rejected';
const STATUS_PROCESSING = 'processing';

// ---------------------------------------------------------------
// Deposit / Withdrawal methods
// ---------------------------------------------------------------
const METHOD_PAYVESSEL = 'payvessel';
const METHOD_BANK      = 'bank';
const METHOD_USDT      = 'usdt';

// ---------------------------------------------------------------
// Mining
// ---------------------------------------------------------------
const MINING_STATUS_ACTIVE    = 'active';
const MINING_STATUS_PAUSED    = 'paused';
const MINING_STATUS_COMPLETED = 'completed';

// ---------------------------------------------------------------
// Tasks
// ---------------------------------------------------------------
const TASK_PLATFORM_FACEBOOK  = 'facebook';
const TASK_PLATFORM_TELEGRAM  = 'telegram';
const TASK_PLATFORM_INSTAGRAM = 'instagram';
const TASK_PLATFORM_WHATSAPP  = 'whatsapp';
const TASK_PLATFORM_TIKTOK    = 'tiktok';
const TASK_PLATFORM_WEBSITE   = 'website';
const TASK_PLATFORM_CUSTOM    = 'custom';

// ---------------------------------------------------------------
// Notifications
// ---------------------------------------------------------------
const NOTIFY_TYPE_DEPOSIT   = 'deposit';
const NOTIFY_TYPE_WITHDRAWAL = 'withdrawal';
const NOTIFY_TYPE_MINING    = 'mining';
const NOTIFY_TYPE_REFERRAL  = 'referral';
const NOTIFY_TYPE_TASK      = 'task';
const NOTIFY_TYPE_SYSTEM    = 'system';
const NOTIFY_TYPE_BROADCAST = 'broadcast';

// ---------------------------------------------------------------
// Token lifetimes (seconds)
// ---------------------------------------------------------------
const EMAIL_VERIFICATION_TTL = 86400;      // 24 hours
const PASSWORD_RESET_TTL     = 3600;       // 1 hour
const REMEMBER_ME_TTL        = 60 * 60 * 24 * 30; // 30 days

// ---------------------------------------------------------------
// Security
// ---------------------------------------------------------------
const MAX_LOGIN_ATTEMPTS      = 5;
const LOGIN_LOCKOUT_SECONDS   = 900; // 15 minutes
const BCRYPT_COST             = 12;

// ---------------------------------------------------------------
// Daily check-in
// ---------------------------------------------------------------
const CHECKIN_CYCLE_DAYS      = 30;
const CHECKIN_DAY7_MULTIPLIER = 3;
const CHECKIN_DAY30_MULTIPLIER = 5;
