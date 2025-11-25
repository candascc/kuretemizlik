# Manual Test Checklist - Implementation Fixes

**Purpose**: Validate all implementation fixes manually before production deployment

**Created**: 2025-11-05 (Self-Audit Fix)

**Status**: Ready for execution

---

## Pre-Deployment Validation

### Environment Setup
- [ ] Testing performed on staging/dev environment (NOT production)
- [ ] Database backup created
- [ ] JWT_SECRET configured in env.local
- [ ] All modified files deployed to test environment

---

## CRITICAL FIX VALIDATION (P0)

### ✅ CRIT-006: JWT Secret Fix

**Test ID**: JWT-001
**File**: `src/Lib/JWTAuth.php`
**Fix**: Removed hardcoded secret, enforced environment variable

**Manual Test Steps**:

1. **Test 1: Missing JWT_SECRET**
   - [ ] Comment out `JWT_SECRET` in env.local
   - [ ] Try to generate JWT token (API call or direct)
   - [ ] ✅ Expected: Exception thrown with clear message
   - [ ] ❌ Failure: Token generated or unclear error

2. **Test 2: Valid JWT_SECRET**
   - [ ] Uncomment `JWT_SECRET` in env.local
   - [ ] Generate JWT token
   - [ ] ✅ Expected: Token generated successfully
   - [ ] Verify token format (3 parts separated by dots)

3. **Test 3: JWT Token Validation**
   - [ ] Use generated token for API authentication
   - [ ] ✅ Expected: Authentication successful
   - [ ] Try with modified token
   - [ ] ✅ Expected: Authentication failed

**Pass Criteria**: All 3 tests pass
**Result**: ☐ PASS ☐ FAIL
**Notes**: _______________________

---

### ✅ CRIT-005: Session Fixation Fix

**Test ID**: AUTH-001 to AUTH-004
**Files**: `ResidentController.php`, `Auth.php`, `TwoFactorController.php`, `PortalController.php`
**Fix**: Added session_regenerate_id() to all authentication flows

**Manual Test Steps**:

#### Test AUTH-001: Resident Portal Login
1. **Setup**:
   - [ ] Open browser developer tools (F12)
   - [ ] Go to Application → Cookies
   - [ ] Note current session ID

2. **Execute**:
   - [ ] Login to resident portal (use valid credentials)
   - [ ] Check session ID in cookies after login

3. **Validate**:
   - [ ] ✅ Expected: Session ID changed after login
   - [ ] ❌ Failure: Session ID remained the same

**Result**: ☐ PASS ☐ FAIL
**Session ID Before**: _______________________
**Session ID After**: _______________________

#### Test AUTH-002: Remember-Me Auto-Login
1. **Setup**:
   - [ ] Login with "Remember Me" checked
   - [ ] Close browser completely
   - [ ] Reopen browser
   - [ ] Note session ID before auto-login

2. **Execute**:
   - [ ] Visit application (should auto-login)
   - [ ] Check session ID after auto-login

3. **Validate**:
   - [ ] ✅ Expected: Session ID changed during auto-login
   - [ ] ❌ Failure: Session ID remained the same

**Result**: ☐ PASS ☐ FAIL

#### Test AUTH-003: Two-Factor Authentication
1. **Setup**:
   - [ ] Enable 2FA for a test user
   - [ ] Note session ID after password entry

2. **Execute**:
   - [ ] Enter 2FA code
   - [ ] Check session ID after 2FA verification

3. **Validate**:
   - [ ] ✅ Expected: Session ID changed after 2FA
   - [ ] ❌ Failure: Session ID remained the same

**Result**: ☐ PASS ☐ FAIL

#### Test AUTH-004: Customer Portal Login
1. **Setup**:
   - [ ] Note session ID before customer login

2. **Execute**:
   - [ ] Login to customer portal
   - [ ] Check session ID after login

3. **Validate**:
   - [ ] ✅ Expected: Session ID changed after login
   - [ ] ❌ Failure: Session ID remained the same

**Result**: ☐ PASS ☐ FAIL

**Overall Auth Tests**: ☐ ALL PASS ☐ SOME FAIL

---

### ✅ CRIT-007 & CRIT-004: Payment Transaction Atomicity

**Test ID**: PAY-001 to PAY-003
**Files**: `PaymentService.php`, `ManagementFee.php`
**Fix**: Wrapped payment processing in database transactions

**Manual Test Steps**:

#### Test PAY-001: Successful Payment
1. **Setup**:
   - [ ] Create test management fee (100 TL)
   - [ ] Note fee ID and initial paid_amount

2. **Execute**:
   - [ ] Process payment (50 TL)
   - [ ] Check payment status
   - [ ] Check management_fee paid_amount
   - [ ] Check money_entries for new entry

3. **Validate**:
   - [ ] ✅ Payment status = 'completed'
   - [ ] ✅ Fee paid_amount increased by 50 TL
   - [ ] ✅ Money entry created with correct amount
   - [ ] ❌ Failure: Any of above not true

**Result**: ☐ PASS ☐ FAIL
**Fee Before**: _______ TL
**Fee After**: _______ TL
**Money Entry ID**: _______

#### Test PAY-002: Payment Failure (Simulated)
1. **Setup**:
   - [ ] Temporarily modify PaymentService to force failure
   - [ ] Create test fee
   - [ ] Note initial state

2. **Execute**:
   - [ ] Attempt payment
   - [ ] Check payment status
   - [ ] Check fee paid_amount
   - [ ] Check money_entries

3. **Validate**:
   - [ ] ✅ Payment status = 'failed' or 'pending'
   - [ ] ✅ Fee paid_amount unchanged
   - [ ] ✅ No new money entry created
   - [ ] ❌ Failure: Partial update occurred

**Result**: ☐ PASS ☐ FAIL

#### Test PAY-003: Database Integrity Check
1. **Execute**:
   - [ ] Run: `php db/migrations/validate_orphaned_records.php`

2. **Validate**:
   - [ ] ✅ No orphaned payment records
   - [ ] ✅ No orphaned money entries
   - [ ] ✅ All FK references valid

**Result**: ☐ PASS ☐ FAIL

**Overall Payment Tests**: ☐ ALL PASS ☐ SOME FAIL

---

## PERFORMANCE VALIDATION

### ✅ CRIT-001: OPcache Reset Removal

**Test ID**: PERF-001
**File**: `index.php`
**Fix**: Removed opcache_reset() call

**Manual Test Steps**:

1. **Test 1: OPcache Status**
   - [ ] Run: `php -r "phpinfo();" | grep opcache.enable`
   - [ ] ✅ Expected: opcache.enable = On

2. **Test 2: Performance Comparison**
   - [ ] Measure average response time (10 requests)
   - [ ] Compare to baseline (if available)
   - [ ] ✅ Expected: Response time ≤ 250ms

**Result**: ☐ PASS ☐ FAIL
**Avg Response Time**: _______ ms

---

## DATA INTEGRITY VALIDATION

### ✅ HIGH-006: FK Cascade Enforcement

**Test ID**: DB-001 to DB-003
**Files**: `db/install.sql`, `db/migrations/015_enforce_fk_cascades.php`
**Fix**: Added ON DELETE CASCADE/SET NULL to foreign keys

**Manual Test Steps**:

#### Test DB-001: Orphaned Records Check
1. **Execute**:
   - [ ] Run: `php db/migrations/validate_orphaned_records.php`

2. **Validate**:
   - [ ] ✅ Zero critical issues
   - [ ] ✅ Zero or acceptable warnings
   - [ ] ❌ Failure: Critical issues found

**Result**: ☐ PASS ☐ FAIL
**Critical Issues**: _______
**Warnings**: _______

#### Test DB-002: Customer Deletion Cascade
1. **Setup**:
   - [ ] Create test customer with jobs
   - [ ] Note job IDs associated with customer

2. **Execute**:
   - [ ] Delete customer
   - [ ] Check if jobs were deleted (CASCADE)

3. **Validate**:
   - [ ] ✅ Jobs deleted automatically
   - [ ] ❌ Failure: Jobs remain (orphaned)

**Result**: ☐ PASS ☐ FAIL

#### Test DB-003: Address Update Preservation
1. **Setup**:
   - [ ] Create customer with address
   - [ ] Create job referencing address
   - [ ] Note address_id

2. **Execute**:
   - [ ] Update customer (modify address)
   - [ ] Check job's address_id

3. **Validate**:
   - [ ] ✅ Job's address_id unchanged (preserved)
   - [ ] ❌ Failure: Job's address_id changed or NULL

**Result**: ☐ PASS ☐ FAIL

**Overall DB Tests**: ☐ ALL PASS ☐ SOME FAIL

---

### ✅ MED-011: Customer Address Update

**Test ID**: ADDR-001
**File**: `src/Models/Customer.php`
**Fix**: Refactored address update logic to preserve IDs

**Manual Test Steps**:

1. **Setup**:
   - [ ] Create customer with 2 addresses
   - [ ] Create job referencing address #1
   - [ ] Note address IDs and job's address_id

2. **Execute**:
   - [ ] Update customer: modify address #1 text
   - [ ] Check address #1 ID
   - [ ] Check job's address_id

3. **Validate**:
   - [ ] ✅ Address #1 ID unchanged
   - [ ] ✅ Address #1 text updated
   - [ ] ✅ Job's address_id still valid
   - [ ] ❌ Failure: Address ID changed or job orphaned

**Result**: ☐ PASS ☐ FAIL
**Address ID Before**: _______
**Address ID After**: _______

---

## REGRESSION TESTS

### Existing Functionality Validation

**Purpose**: Ensure fixes didn't break existing features

#### Test REG-001: Job Creation
- [ ] Create new job
- [ ] ✅ Expected: Job created successfully
- [ ] ❌ Failure: Error or unexpected behavior

#### Test REG-002: Payment Processing (Normal)
- [ ] Process payment through normal flow
- [ ] ✅ Expected: Payment processes normally
- [ ] ❌ Failure: Error or unexpected behavior

#### Test REG-003: User Authentication (Normal)
- [ ] Login/logout multiple times
- [ ] ✅ Expected: Auth works normally
- [ ] ❌ Failure: Error or unexpected behavior

#### Test REG-004: Customer Management
- [ ] Create, update, delete customer
- [ ] ✅ Expected: CRUD operations work normally
- [ ] ❌ Failure: Error or unexpected behavior

**Overall Regression**: ☐ ALL PASS ☐ SOME FAIL

---

## USER ACCEPTANCE TESTS (UAT)

### End-to-End Scenarios

#### Scenario 1: Complete Payment Flow
1. [ ] Login as resident
2. [ ] View pending fees
3. [ ] Initiate online payment
4. [ ] Complete payment
5. [ ] Verify payment confirmation
6. [ ] Check fee status updated

**Result**: ☐ PASS ☐ FAIL
**Issues**: _______________________

#### Scenario 2: Customer Management Flow
1. [ ] Login as admin
2. [ ] Create new customer with addresses
3. [ ] Create job for customer
4. [ ] Update customer address
5. [ ] Verify job still references correct address

**Result**: ☐ PASS ☐ FAIL
**Issues**: _______________________

---

## SECURITY VALIDATION

### Security Checklist

- [ ] No hardcoded secrets in source code
- [ ] Session IDs regenerate on authentication
- [ ] JWT tokens expire correctly
- [ ] CSRF protection working
- [ ] Input sanitization functioning
- [ ] SQL injection prevention active

**Security Test Result**: ☐ ALL PASS ☐ SOME FAIL

---

## FINAL SIGN-OFF

### Pre-Production Checklist

- [ ] All critical tests passed (CRIT-001 to CRIT-007)
- [ ] All data integrity tests passed
- [ ] Regression tests passed
- [ ] UAT scenarios completed successfully
- [ ] Security validation passed
- [ ] Performance acceptable
- [ ] No critical errors in logs

### Production Readiness

☐ **READY FOR PRODUCTION** - All tests passed
☐ **NOT READY** - Issues found (see notes)

**Tested By**: _______________________
**Date**: _______________________
**Signature**: _______________________

**Notes/Issues**:
_______________________________________________________
_______________________________________________________
_______________________________________________________

---

## Automated Test Results (Reference)

Run automated tests before manual testing:

```bash
# 1. Run all automated tests
php tests/run_all_tests.php

# 2. Check functional tests specifically
php tests/functional/run_all.php

# 3. Validate database
php db/migrations/validate_orphaned_records.php
```

**Automated Test Results**: 
- Functional Tests: ☐ PASS ☐ FAIL
- DB Validation: ☐ PASS ☐ FAIL

---

**Document Version**: 1.0
**Last Updated**: 2025-11-05
**Self-Audit Fix**: P1.4 - Manual test checklist

