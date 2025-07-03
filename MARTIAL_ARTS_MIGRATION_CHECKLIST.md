# 🥋 Martial Arts Membership Migration Checklist

## 🚨 **IMPORTANT: Complete Each Step in Order**

### **📋 Pre-Migration (Required)**

- [ ] **Backup Database**

  ```bash
  # Create backup before any changes
  mysqldump -u root -p testbook > backup_before_martial_arts_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **Verify Current Setup**
  - [ ] Admin access working: `http://localhost/Testbook/admin/login.php`
  - [ ] Current memberships displaying: `http://localhost/Testbook/admin/memberships.php`
  - [ ] Note any existing active user memberships

### **🔧 Step 1: Run Database Migration**

1. **Access Migration Interface**

   - Go to: `http://localhost/Testbook/admin/run_membership_migration.php`
   - Login with admin credentials

2. **Review Migration Summary**

   - Check the preview of changes
   - Ensure you understand what will be modified

3. **Execute Migration**
   - Click "Run Migration"
   - Wait for completion message
   - Check for any error messages

### **📝 Step 2: Add Your Martial Arts Membership Plans**

1. **Go to Admin Memberships**

   - Navigate to: `http://localhost/Testbook/admin/memberships.php`

2. **Add Your New Plans (in this order):**

   **INFANTS (4-6 years):**

   - Name: `Infants (4-6 years)`
   - Price: `£20.00`
   - Monthly Limit: `4` (1 per week)
   - Age Min: `4`, Age Max: `6`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSAZ4PZ`

   **JUNIORS Basic (7-11 years):**

   - Name: `Juniors Basic (7-11 years)`
   - Price: `£30.00`
   - Monthly Limit: `4` (1 per week)
   - Age Min: `7`, Age Max: `11`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSC2WQZ`

   **JUNIORS Unlimited (7-11 years):**

   - Name: `Juniors Unlimited (7-11 years)`
   - Price: `£50.00`
   - Monthly Limit: `NULL` (unlimited)
   - Age Min: `7`, Age Max: `11`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSD6F9Q`

   **SENIOR SCHOOL Basic (11-15 years):**

   - Name: `Senior School Basic (11-15 years)`
   - Price: `£30.00`
   - Monthly Limit: `4` (1 per week)
   - Age Min: `11`, Age Max: `15`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSC2WQZ`

   **SENIOR SCHOOL Unlimited (11-15 years):**

   - Name: `Senior School Unlimited (11-15 years)`
   - Price: `£50.00`
   - Monthly Limit: `NULL` (unlimited)
   - Age Min: `11`, Age Max: `15`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSD6F9Q`

   **SENIOR SCHOOL PAYG Sparring (14+ years):**

   - Name: `Senior Sparring PAYG (14+ invitation only)`
   - Price: `£10.00`
   - Monthly Limit: `1`
   - Age Min: `14`, Age Max: `15`
   - Mark as PAYG: `Yes`
   - Requires Invitation: `Yes`

   **ADULT Beginner Deal (15+ years):**

   - Name: `Adult Beginner Deal (15+ years)`
   - Price: `£40.00`
   - Monthly Limit: `4` (1 per week)
   - Age Min: `15`
   - Mark as Beginner Only: `Yes`
   - Duration: `12 weeks`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSNW5ZN`

   **ADULT Basic (15+ years):**

   - Name: `Adult Basic (15+ years)`
   - Price: `£65.00`
   - Monthly Limit: `8` (2 per week)
   - Age Min: `15`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSFCSTK`

   **ADULT Unlimited (15+ years):**

   - Name: `Adult Unlimited (15+ years)`
   - Price: `£85.00`
   - Monthly Limit: `NULL` (unlimited)
   - Age Min: `15`
   - GoCardless URL: `https://pay.gocardless.com/BRT0003YQSG794H`

### **🧪 Step 3: Testing Phase**

- [ ] **Test Age-Based Plan Display**

  - Create test users with different ages
  - Verify correct plans show for each age group

- [ ] **Test Membership Limits**

  - Try booking classes with limited membership
  - Verify unlimited memberships work properly

- [ ] **Test GoCardless Integration**

  - Click payment links to verify they work
  - Check payment flow (don't complete actual payments)

- [ ] **Test Admin Interface**
  - View memberships list
  - Check user details show age-appropriate plans

### **✅ Step 4: Verification Checklist**

- [ ] **Database Structure**

  ```sql
  -- Run this query to verify new columns exist:
  DESCRIBE membership_plans;
  ```

- [ ] **Sample Data Check**

  ```sql
  -- Verify plans were added correctly:
  SELECT name, price, age_min, age_max, monthly_class_limit
  FROM membership_plans
  WHERE age_min IS NOT NULL;
  ```

- [ ] **User Age Function Test**
  ```sql
  -- Test age calculation works:
  SELECT id, first_name, last_name, date_of_birth,
         TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) as age
  FROM users
  WHERE date_of_birth IS NOT NULL;
  ```

### **🔄 Step 5: Update Classes (If Needed)**

If you need to update your classes from Yoga/Pilates to Martial Arts:

- [ ] **Add Martial Arts Classes**
  - Go to: `http://localhost/Testbook/admin/classes.php`
  - Add your Krav Maga class types:
    - Infants (4-6)
    - Juniors (7-11)
    - Senior School (11-15)
    - Adult Fundamentals (15+)
    - Adult Advanced (15+)
    - Adult Sparring (14+ invitation only)

### **🚨 Rollback Plan (If Needed)**

If something goes wrong:

1. **Restore Database Backup**

   ```bash
   mysql -u root -p testbook < backup_before_martial_arts_YYYYMMDD_HHMMSS.sql
   ```

2. **Remove Migration Files**
   - Delete the migration files if needed
   - Restart with fresh backup

### **📞 Success Verification**

✅ **Your system is ready when:**

- [ ] All 9 membership plans visible in admin
- [ ] Age-based plan selection works for users
- [ ] GoCardless links functional
- [ ] Existing user memberships preserved
- [ ] Class booking respects membership limits

---

## 🆘 **Need Help?**

If you encounter any issues:

1. Check the error logs in your admin interface
2. Verify database connection
3. Ensure all GoCardless URLs are correct
4. Test with different user ages

**Your martial arts membership system will be fully operational!** 🥋✨
