# Rollback Procedures - Implementation Fixes

**Purpose**: Detailed rollback instructions for all self-audit fixes

**Created**: 2025-11-05

**CRITICAL**: Follow these procedures if any implementation fix causes issues in production

---

## Emergency Rollback Contact

**Procedure**: If critical issue in production, follow these steps **immediately**:
1. Notify team/stakeholders
2. Take database backup (if not done automatically)
3. Follow specific rollback procedure below
4. Verify system operation
5. Document incident

---

## Rollback Decision Matrix

| Severity | Action | Rollback Required? |
|----------|--------|-------------------|
| **P0 - System Down** | Immediate rollback | YES |
| **P1 - Critical Feature Broken** | Rollback within 1 hour | YES |
| **P2 - Minor Feature Issue** | Fix forward or rollback | EVALUATE |
| **P3 - Cosmetic Issue** | Fix forward | NO |

---

## 1. JWT Secret Fix Rollback (CRIT-006)

### Affected Files
- `src/Lib/JWTAuth.php`
- `env.local`
- `env.example`

### Symptoms Requiring Rollback
- API authentication completely broken
- All JWT token generation failing
- Exception: "JWT_SECRET environment variable is required"
- Unable to login to any API endpoint

### Rollback Procedure

**Severity**: P0 if API is completely down, P2 if partial

**Time to Rollback**: 5 minutes

**Steps**:

1. **Backup Current Files**
   ```bash
   cp src/Lib/JWTAuth.php src/Lib/JWTAuth.php.new.bak
   cp env.local env.local.new.bak
   ```

2. **Restore Original JWTAuth.php**
   
   Option A: From Git (if tracked)
   ```bash
   git checkout HEAD~1 src/Lib/JWTAuth.php
   ```
   
   Option B: Manual restoration (add back hardcoded secret temporarily)
   ```php
   // In src/Lib/JWTAuth.php, around line 8
   private static $secretKey = 'kuretemizlik_jwt_secret_key_2024'; // TEMPORARY ROLLBACK
   
   // In getSecretKeys() method, change to:
   private static function getSecretKeys(): array
   {
       $primary = $_ENV['JWT_SECRET'] ?? self::$secretKey; // Fallback to hardcoded
       $previous = $_ENV['JWT_SECRET_PREVIOUS'] ?? null;
       return $previous ? [$primary, $previous] : [$primary];
   }
   ```

3. **Clear OPcache**
   ```bash
   # If you have access to server
   service php-fpm reload
   # Or via PHP
   php -r "opcache_reset();"
   ```

4. **Verify API Works**
   ```bash
   # Test token generation
   curl -X POST https://yoursite.com/api/v2/auth/login \
     -H "Content-Type: application/json" \
     -d '{"username":"test","password":"test"}'
   ```

5. **Remove JWT_SECRET from env.local** (if causing issues)
   ```bash
   # Comment out temporarily
   # JWT_SECRET=...
   ```

**Verification**:
- [ ] API authentication works
- [ ] Existing tokens still validate
- [ ] New tokens generate successfully
- [ ] No errors in logs

**Post-Rollback**:
- Document why rollback was necessary
- Schedule proper fix implementation
- Keep backup of attempted fix for analysis

---

## 2. Session Fixation Fix Rollback (CRIT-005)

### Affected Files
- `src/Controllers/ResidentController.php`
- `src/Lib/Auth.php`
- `src/Controllers/TwoFactorController.php`
- `src/Controllers/PortalController.php`

### Symptoms Requiring Rollback
- Users unable to login (any portal)
- Session errors on authentication
- "Session could not be regenerated" errors
- Constant logouts/session loss

### Rollback Procedure

**Severity**: P0 if login completely broken, P1 if affecting many users

**Time to Rollback**: 10 minutes

**Steps**:

1. **Backup Current Files**
   ```bash
   cp src/Controllers/ResidentController.php src/Controllers/ResidentController.php.new.bak
   cp src/Lib/Auth.php src/Lib/Auth.php.new.bak
   cp src/Controllers/TwoFactorController.php src/Controllers/TwoFactorController.php.new.bak
   cp src/Controllers/PortalController.php src/Controllers/PortalController.php.new.bak
   ```

2. **Remove Session Regeneration Calls**
   
   In each affected file, remove or comment out:
   ```php
   // REMOVE THIS BLOCK:
   if (session_status() === PHP_SESSION_ACTIVE) {
       session_regenerate_id(true);
   }
   ```
   
   Locations:
   - `ResidentController.php`: After line ~72 (after password verification)
   - `Auth.php` (checkRememberMe): After line ~90 (before completeLogin)
   - `TwoFactorController.php`: After line ~85 (after 2FA verification)
   - `PortalController.php`: After line ~72 (after customer auth)

3. **Clear Sessions** (Optional, if needed)
   ```bash
   # Clear session files if they're corrupted
   rm -rf /var/lib/php/sessions/*
   # Or via PHP
   php -r "session_start(); session_destroy();"
   ```

4. **Test Login Flows**
   - Test resident portal login
   - Test admin login with remember-me
   - Test 2FA flow
   - Test customer portal login

**Verification**:
- [ ] Resident login works
- [ ] Remember-me works
- [ ] 2FA works
- [ ] Customer portal login works
- [ ] No session errors

**Post-Rollback**:
- Session fixation vulnerability is re-introduced
- Schedule proper fix with additional testing
- Monitor for session-related attacks

---

## 3. Payment Transaction Rollback (CRIT-007)

### Affected Files
- `src/Services/PaymentService.php`
- `src/Models/ManagementFee.php`

### Symptoms Requiring Rollback
- Payment processing completely broken
- Database deadlocks during payment
- Payments completing but fees not updating
- Transaction timeout errors

### Rollback Procedure

**Severity**: P0 if payments broken, P1 if partial issues

**Time to Rollback**: 15 minutes

**Steps**:

1. **Backup Current Files**
   ```bash
   cp src/Services/PaymentService.php src/Services/PaymentService.php.new.bak
   cp src/Models/ManagementFee.php src/Models/ManagementFee.php.new.bak
   ```

2. **Restore Original PaymentService.php**
   
   Remove transaction wrapper from `processPayment()`:
   ```php
   // BEFORE (with transaction):
   $transactionResult = $this->db->transaction(function() use (...) {
       // ... payment processing
   });
   
   // AFTER (without transaction - ROLLBACK):
   // Just execute directly without transaction wrapper
   $this->updatePaymentStatus($paymentId, 'processing', $providerData);
   $result = $this->processWithProvider($payment, $providerData);
   // ... rest of logic
   ```

3. **Restore Original ManagementFee.php**
   
   Remove transaction wrapper from `applyPayment()`:
   ```php
   // Remove: return $this->db->transaction(function() use (...) {
   // Just execute directly
   $paid = (float)($row['paid_amount'] ?? 0) + max(0, (float)$amount);
   // ... rest of logic without transaction wrapper
   ```

4. **Move Notification Back Inside** (if needed)
   
   In `PaymentService.php`, move notification send back inside processing logic:
   ```php
   if ($result['success']) {
       $this->updatePaymentStatus($paymentId, 'completed', ...);
       $feeModel->applyPayment(...);
       
       // MOVE BACK INSIDE:
       try {
           $this->notificationService->sendPaymentConfirmation(...);
       } catch (Exception $e) {
           error_log("Notification error: " . $e->getMessage());
       }
       
       return ['success' => true, 'message' => '...'];
   }
   ```

5. **Test Payment Processing**
   ```bash
   # Test payment creation
   # Test payment processing
   # Verify fee updates
   # Check money entries
   ```

**Verification**:
- [ ] Payments can be created
- [ ] Payments can be processed
- [ ] Fees update correctly
- [ ] Money entries created
- [ ] No database locks
- [ ] Notifications send

**Post-Rollback**:
- ⚠️ **WARNING**: Atomicity is lost - partial transactions possible
- Monitor for data inconsistencies
- Schedule proper fix with better testing

---

## 4. OPcache Reset Removal Rollback (CRIT-001)

### Affected Files
- `index.php`

### Symptoms Requiring Rollback
- Cached code causing issues
- Code changes not reflecting
- Unexpected behavior after deployments

### Rollback Procedure

**Severity**: P2 (unlikely to need rollback)

**Time to Rollback**: 2 minutes

**Steps**:

1. **Restore opcache_reset() Call**
   
   In `index.php`, add back at top:
   ```php
   <?php
   /**
    * Temizlik İş Takip Uygulaması - Ana Router
    */
   
   // ROLLBACK: Re-enable opcache reset
   if (function_exists('opcache_reset')) {
       opcache_reset();
   }
   
   require_once __DIR__ . '/config/config.php';
   ```

2. **Clear OPcache Manually**
   ```bash
   service php-fpm reload
   ```

**Verification**:
- [ ] Code changes reflect immediately
- [ ] No caching issues

**Post-Rollback**:
- ⚠️ Performance will degrade (cache reset every request)
- This is temporary measure only
- Fix root cause of caching issue

---

## 5. FK Cascade Migration Rollback (HIGH-006)

### Affected Files
- `db/install.sql`
- Database tables: `jobs`, `money_entries`, `activity_log`

### Symptoms Requiring Rollback
- Database errors on delete operations
- Orphaned records appearing
- FK constraint violations
- Data loss from cascades

### Rollback Procedure

**Severity**: P0 if data loss, P1 if FK errors

**Time to Rollback**: 20 minutes

**Steps**:

1. **STOP APPLICATION IMMEDIATELY**
   ```bash
   # Maintenance mode or stop web server
   touch maintenance.flag
   ```

2. **Restore Database from Backup**
   ```bash
   # Find backup file
   ls -lh db/backups/pre-fk-migration-*.sqlite
   
   # Restore from backup
   cp db/app.sqlite db/app.sqlite.corrupted.bak
   cp db/backups/pre-fk-migration-YYYYMMDD-HHMMSS.sqlite db/app.sqlite
   ```

3. **Verify Database Integrity**
   ```bash
   php db/migrations/validate_orphaned_records.php
   ```

4. **If No Backup Available** (Emergency)
   
   Manually recreate tables without CASCADE:
   ```bash
   # This is complex - contact DBA
   # Requires table recreation + data migration
   # Use migration rollback script
   ```

5. **Restart Application**
   ```bash
   rm maintenance.flag
   ```

**Verification**:
- [ ] Database accessible
- [ ] No FK errors
- [ ] Data intact
- [ ] Application functional

**Post-Rollback**:
- ⚠️ **CRITICAL**: Orphaned records may accumulate
- Run orphaned records cleanup periodically
- Schedule proper migration with testing

---

## 6. Customer Address Update Rollback (MED-011)

### Affected Files
- `src/Models/Customer.php`

### Symptoms Requiring Rollback
- Customer update failing
- Address updates causing errors
- Jobs losing address references
- Transaction timeout on customer update

### Rollback Procedure

**Severity**: P1 if customer updates broken

**Time to Rollback**: 10 minutes

**Steps**:

1. **Backup Current File**
   ```bash
   cp src/Models/Customer.php src/Models/Customer.php.new.bak
   ```

2. **Restore Original Address Logic**
   
   In `Customer.php` `update()` method, change to simple delete-insert:
   ```php
   // ROLLBACK: Simple delete-insert (original logic)
   if (isset($data['addresses'])) {
       // Delete all existing addresses
       $this->db->delete('addresses', 'customer_id = ?', [$id]);
       
       // Insert new addresses
       foreach ($data['addresses'] as $address) {
           if (empty($address['line'])) continue;
           
           $this->db->insert('addresses', [
               'customer_id' => $id,
               'label' => $address['label'] ?? null,
               'line' => $address['line'],
               'city' => $address['city'] ?? null,
               'created_at' => date('Y-m-d H:i:s')
           ]);
       }
   }
   ```

3. **Remove handleRemovedAddress() Method** (added in fix)

4. **Test Customer Update**
   ```bash
   # Update customer via admin panel
   # Verify addresses updated
   # Check jobs still reference addresses (may be broken)
   ```

**Verification**:
- [ ] Customer updates work
- [ ] Addresses can be modified
- [ ] No errors on save

**Post-Rollback**:
- ⚠️ **WARNING**: Job address references may break
- Monitor for orphaned job_address references
- Update jobs manually if needed

---

## General Rollback Best Practices

### Before Rollback
1. [ ] Document the issue clearly
2. [ ] Take screenshots/logs of errors
3. [ ] Notify stakeholders
4. [ ] Backup current state
5. [ ] Identify affected users

### During Rollback
1. [ ] Follow procedure exactly
2. [ ] Test each step
3. [ ] Keep detailed notes
4. [ ] Monitor error logs
5. [ ] Have backup plan ready

### After Rollback
1. [ ] Verify system fully operational
2. [ ] Monitor for 24-48 hours
3. [ ] Document incident report
4. [ ] Analyze root cause
5. [ ] Plan better fix
6. [ ] Schedule re-implementation with better testing

---

## Emergency Database Restore

If database is corrupted or needs complete restore:

```bash
# 1. Stop application
touch maintenance.flag

# 2. Backup corrupted database
cp db/app.sqlite db/app.sqlite.corrupted.$(date +%Y%m%d-%H%M%S)

# 3. List available backups
ls -lh db/backups/

# 4. Restore from backup
cp db/backups/[BACKUP_FILE].sqlite db/app.sqlite

# 5. Verify database
sqlite3 db/app.sqlite "PRAGMA integrity_check;"

# 6. Restart application
rm maintenance.flag
```

---

## Rollback Testing

**Before Production Deployment**, test rollback procedures:

1. [ ] Deploy fixes to staging
2. [ ] Test each rollback procedure
3. [ ] Measure rollback time
4. [ ] Verify data integrity after rollback
5. [ ] Document any issues found
6. [ ] Update procedures if needed

---

## Contact Information

**Emergency Contacts**:
- System Admin: ___________________
- DBA: ___________________
- Team Lead: ___________________
- On-Call: ___________________

**Escalation Path**:
1. Try rollback procedure
2. If fails, contact System Admin
3. If data issue, contact DBA
4. If critical, contact Team Lead
5. Document everything

---

**Document Version**: 1.0
**Last Updated**: 2025-11-05
**Self-Audit Fix**: P2.4 - Rollback procedures
**Status**: Ready for emergency use

