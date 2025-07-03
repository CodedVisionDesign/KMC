# 🥋 Martial Arts Membership Implementation Guide

This guide will help you smoothly transition your system to the new age-based martial arts membership structure with GoCardless integration.

## 📋 **What You Need Before Starting**

### ✅ **Already in Your System:**

- ✅ GoCardless URL support in membership plans
- ✅ User date_of_birth field for age calculations
- ✅ Payment processing workflow
- ✅ Admin membership management interface

### 🔗 **Your GoCardless Payment Links (Ready to Use):**

- **Infant:** `https://pay.gocardless.com/BRT0003YQSAZ4PZ`
- **Junior/Senior Basic:** `https://pay.gocardless.com/BRT0003YQSC2WQZ`
- **Junior/Senior Unlimited:** `https://pay.gocardless.com/BRT0003YQSD6F9Q`
- **Adult Beginner:** `https://pay.gocardless.com/BRT0003YQSNW5ZN`
- **Adult Basic:** `https://pay.gocardless.com/BRT0003YQSFCSTK`
- **Adult Unlimited:** `https://pay.gocardless.com/BRT0003YQSG794H`

---

## 🚀 **Implementation Steps**

### **Step 1: Backup Your Database**

```bash
# Create a backup before making any changes
mysqldump -u your_username -p your_database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### **Step 2: Run the Migration**

1. **Access Admin Panel:** `http://localhost/Testbook/admin/login.php`
2. **Navigate to Migration:** `http://localhost/Testbook/admin/run_membership_migration.php`
3. **Review System Status:** Check which components need updating
4. **Run Migration:** Click "Run Migration" button
5. **Verify Success:** Confirm all components show "Ready" status

### **Step 3: Verify New Membership Structure**

After migration, you'll have these membership tiers:

#### **ADULTS (15+ years)**

- **£85 - Adult Unlimited:** Unlimited classes
- **£65 - Adult Basic:** Up to 2 classes per week
- **£40 - Adult Beginner Deal:** 1 beginner class/week for 12 weeks max

#### **SENIOR SCHOOL (11-15 years)**

- **£50 - Senior Unlimited:** Unlimited classes
- **£30 - Senior Basic:** 1 class per week
- **£10 - Senior Sparring PAYG:** Sparring only (14+ years, invitation required)

#### **JUNIORS (7-11 years)**

- **£50 - Junior Unlimited:** Unlimited classes
- **£30 - Junior Basic:** 1 class per week

#### **INFANTS (4-6 years)**

- **£20 - Infant Membership:** 1 class per week

### **Step 4: Update Classes (If Needed)**

The migration creates sample martial arts classes, but you may want to customize:

1. **Go to:** Admin → Classes
2. **Available Class Types:**
   - `adults-fundamentals` - Basic techniques for beginners
   - `adults-advanced` - Advanced techniques
   - `adults-any-level` - Mixed level classes
   - `adults-bag-work` - Heavy bag training
   - `adults-sparring` - Sparring (invitation required)
   - `seniors` - Senior school classes (11-15 years)
   - `juniors` - Junior classes (7-11 years)
   - `infants` - Infant classes (4-6 years)
   - `private-tuition` - One-on-one sessions

---

## ⚙️ **New Features & Functionality**

### **🎯 Age-Based Restrictions**

- Users only see membership plans appropriate for their age
- Class booking automatically checks age requirements
- Age calculated from `date_of_birth` field

### **⏰ Special Membership Rules**

#### **Beginner Deal Logic:**

- Automatically expires after 12 weeks
- Restricts access to beginner classes only
- Auto-upgrades to Adult Basic plan after expiry

#### **PAYG Sparring:**

- Requires existing active membership
- Only available to 14+ years
- Invitation-only access

#### **Weekly Limits:**

- Automatically tracks weekly class usage
- Resets every Monday
- Enforced during booking process

### **💳 Payment Integration**

- GoCardless URLs pre-configured for each plan
- Copy-to-clipboard functionality for easy sharing
- Admin can easily provide payment details to customers

---

## 🔧 **Admin Management**

### **Membership Workflow:**

1. **Customer requests membership** → Shows as 'pending'
2. **Admin reviews** → Can see age restrictions and eligibility
3. **Admin approves** → "Approve & Show Payment" button
4. **Payment modal appears** → GoCardless link + bank details
5. **Admin shares payment info** → Customer completes payment
6. **Admin confirms payment** → Membership becomes 'active'

### **Special Administrative Tasks:**

#### **Managing Beginner Upgrades:**

- System automatically detects when beginner deals expire
- Creates pending memberships for upgraded plans
- Admin receives notifications for review

#### **Invitation Management:**

- Sparring classes marked as invitation-only
- Admin can manually approve access (invitation logic extensible)

#### **Age Verification:**

- System prevents booking age-inappropriate classes
- Clear error messages explain restrictions

---

## 📊 **User Experience Changes**

### **For Customers:**

- **Registration:** Date of birth required for age-based memberships
- **Plan Selection:** Only age-appropriate plans displayed
- **Class Booking:** Automatic age and membership validation
- **Clear Limits:** Weekly/monthly limits clearly displayed

### **Error Messages:**

- "Age restriction: Must be at least 15 years old"
- "Active membership required"
- "Weekly class limit reached"
- "Invitation required for this class"

---

## 🛠️ **Technical Implementation**

### **New Database Fields:**

#### **membership_plans:**

- `age_min, age_max` - Age restrictions
- `is_beginner_only` - Beginner deal flag
- `beginner_duration_weeks` - Duration for beginner plans
- `weekly_class_limit` - Weekly class limits
- `requires_invitation` - Invitation-only flag

#### **classes:**

- `class_type` - Martial arts class categorization
- `age_min, age_max` - Class age restrictions
- `requires_invitation` - Invitation requirement

#### **user_memberships:**

- `beginner_start_date, beginner_end_date` - Beginner tracking
- `weekly_classes_used` - Weekly usage counter
- `auto_upgrade_plan_id` - Automatic upgrade target

### **Helper Functions:**

- `GetUserAge()` - Calculate age from birth date
- `CanUserAccessPlan()` - Validate plan eligibility
- `CanUserBookClass()` - Validate class booking

---

## 🧪 **Testing Checklist**

### **Age-Based Access:**

- [ ] Create test users with different ages (5, 10, 13, 16, 25)
- [ ] Verify they only see appropriate membership plans
- [ ] Test class booking age restrictions

### **Special Memberships:**

- [ ] Test beginner deal duration and auto-upgrade
- [ ] Verify PAYG sparring requires existing membership
- [ ] Check invitation-only class restrictions

### **Payment Integration:**

- [ ] Test GoCardless URL copying
- [ ] Verify payment modal shows correct details
- [ ] Test admin approval workflow

### **Weekly Limits:**

- [ ] Book classes and verify weekly counter
- [ ] Test limit enforcement
- [ ] Verify Monday reset (can simulate)

---

## 🚨 **Troubleshooting**

### **Migration Issues:**

```sql
-- Check if migration completed successfully
SHOW COLUMNS FROM membership_plans LIKE 'age_min';
SHOW COLUMNS FROM classes LIKE 'class_type';
SELECT COUNT(*) FROM membership_plans WHERE name LIKE '%Adult%';
```

### **Age Calculation Issues:**

```sql
-- Verify age calculation function
SELECT GetUserAge('1990-01-01') as age;
-- Should return current age for 1990 birth year
```

### **Permission Issues:**

```sql
-- Check if helper functions were created
SHOW FUNCTION STATUS WHERE Name = 'GetUserAge';
SHOW FUNCTION STATUS WHERE Name = 'CanUserAccessPlan';
```

---

## 📞 **Support & Maintenance**

### **Regular Tasks:**

- **Weekly:** Review beginner plan upgrades
- **Monthly:** Check payment confirmations
- **Quarterly:** Review age demographics and plan usage

### **System Monitoring:**

- Monitor database function performance
- Check weekly limit reset automation
- Verify payment link functionality

### **Future Enhancements:**

- Automated email notifications for upgrades
- GoCardless webhook integration
- Advanced invitation management system
- Detailed analytics dashboard

---

## ✅ **Implementation Complete!**

After completing these steps, your system will smoothly handle:

- ✅ **Age-based membership tiers** with automatic validation
- ✅ **Martial arts class structure** with appropriate restrictions
- ✅ **GoCardless payment integration** with your provided links
- ✅ **Special membership rules** (beginner deals, PAYG, invitations)
- ✅ **Automated workflows** for upgrades and limits
- ✅ **Enhanced admin tools** for efficient management

Your martial arts studio now has a professional, automated membership system that handles complex business rules while providing a smooth experience for both admins and customers.

---

**🎉 Ready to launch your new martial arts membership system!**
