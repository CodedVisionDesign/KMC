# Membership System Testing Checklist

## Automated Tests

- [ ] Run `public/test_membership_web.php` - All tests should pass
- [ ] Run `public/bug_scan.php` - No critical bugs should be found

## User Registration & Authentication Flow

- [ ] **New User Registration**
  - [ ] Register with valid email/password
  - [ ] Try registering with duplicate email (should fail)
  - [ ] Try registering with weak password (should fail)
  - [ ] Complete health questionnaire
  - [ ] Auto-login after successful registration
- [ ] **User Login/Logout**
  - [ ] Login with valid credentials
  - [ ] Login with invalid credentials (should fail)
  - [ ] Logout functionality works
  - [ ] Session persistence across page reloads

## Free Trial Flow

- [ ] **New User Free Trial**

  - [ ] New user can see "Free trial available" status
  - [ ] New user can book their first class without membership
  - [ ] Free trial booking shows appropriate success message
  - [ ] After free trial booking, user marked as trial used
  - [ ] User cannot book second class without membership

- [ ] **Free Trial Edge Cases**
  - [ ] User cannot book multiple free trial classes
  - [ ] Free trial status correctly displayed on dashboard
  - [ ] Free trial users have video access

## Membership Purchase Flow

- [ ] **Membership Selection**

  - [ ] Membership page shows all available plans
  - [ ] Plan details (price, class limits) display correctly
  - [ ] User can select different membership tiers
  - [ ] Payment recording functionality works (admin side)

- [ ] **Membership Status**
  - [ ] Active membership displays correctly on dashboard
  - [ ] Membership expiration dates are accurate
  - [ ] Monthly class counters work correctly

## Class Booking Flow

- [ ] **Booking Validation**

  - [ ] Users without membership cannot book (after free trial)
  - [ ] Users with active membership can book within limits
  - [ ] Monthly class limits are enforced
  - [ ] Fully booked classes cannot be over-booked
  - [ ] Users cannot double-book the same class

- [ ] **Booking Success**
  - [ ] Successful bookings show appropriate messages
  - [ ] Class availability updates in real-time
  - [ ] Booking counts toward monthly limits
  - [ ] Membership cycle tracking works correctly

## Monthly Limit Enforcement

- [ ] **Basic Plan (4 classes/month)**
  - [ ] User can book up to 4 classes per month
  - [ ] 5th booking attempt is blocked
  - [ ] Counter resets at month boundary
- [ ] **Standard Plan (8 classes/month)**
  - [ ] User can book up to 8 classes per month
  - [ ] 9th booking attempt is blocked
- [ ] **Premium Plan (12 classes/month)**
  - [ ] User can book up to 12 classes per month
  - [ ] 13th booking attempt is blocked
- [ ] **Unlimited Plan**
  - [ ] User can book unlimited classes
  - [ ] No monthly limit restrictions

## Video Content Access

- [ ] **Access Control**
  - [ ] Free trial users can access videos
  - [ ] Active membership holders can access videos
  - [ ] Users without membership cannot access videos
  - [ ] Video modal functionality works
  - [ ] Video series display correctly

## Admin Functionality

- [ ] **Payment Management**

  - [ ] Admin can record membership payments
  - [ ] Payment history displays correctly
  - [ ] Payment status tracking works

- [ ] **User Management**
  - [ ] Admin can view user memberships
  - [ ] Admin can modify membership status
  - [ ] Admin can track class usage

## Error Handling & Edge Cases

- [ ] **API Error Responses**

  - [ ] Unauthenticated requests return 401
  - [ ] Invalid class IDs return 404
  - [ ] Membership required errors return 403
  - [ ] Full classes return 409
  - [ ] Server errors return 500

- [ ] **Database Integrity**

  - [ ] No orphaned membership records
  - [ ] No users with multiple active memberships
  - [ ] Expired memberships properly handled
  - [ ] Booking cycles properly tracked

- [ ] **Session Management**
  - [ ] Sessions persist across page loads
  - [ ] Session timeout works correctly
  - [ ] Multiple browser tabs work correctly
  - [ ] Logout clears session properly

## User Experience

- [ ] **Navigation**

  - [ ] All navigation links work
  - [ ] Membership status visible in navigation
  - [ ] Appropriate redirects on login/logout

- [ ] **Visual Feedback**

  - [ ] Success messages display correctly
  - [ ] Error messages are user-friendly
  - [ ] Loading states work properly
  - [ ] Class availability indicators accurate

- [ ] **Mobile Responsiveness**
  - [ ] All pages work on mobile devices
  - [ ] Video player works on mobile
  - [ ] Forms are mobile-friendly
  - [ ] Navigation works on small screens

## Performance & Security

- [ ] **Performance**

  - [ ] Pages load quickly
  - [ ] Database queries are optimized
  - [ ] No memory leaks or excessive resource usage

- [ ] **Security**
  - [ ] Passwords are properly hashed
  - [ ] SQL injection protection
  - [ ] XSS protection
  - [ ] CSRF protection on forms
  - [ ] Session security

## Data Consistency

- [ ] **Membership Data**

  - [ ] Start/end dates are accurate
  - [ ] Class counts match actual bookings
  - [ ] Payment records align with memberships
  - [ ] Free trial flags are consistent

- [ ] **Booking Data**
  - [ ] Membership cycles are correctly assigned
  - [ ] Free trial bookings properly flagged
  - [ ] User information is accurate
  - [ ] Class capacity calculations correct

## Integration Testing

- [ ] **End-to-End Scenarios**
  - [ ] Complete new user journey (register → free trial → membership → multiple bookings)
  - [ ] Membership renewal process
  - [ ] Monthly limit reset verification
  - [ ] Video access throughout membership lifecycle

## Browser Compatibility

- [ ] **Chrome** - All functionality works
- [ ] **Firefox** - All functionality works
- [ ] **Safari** - All functionality works
- [ ] **Edge** - All functionality works

## Common Bug Scenarios to Test

1. **Double Free Trial**: Try to use free trial twice with same user
2. **Limit Bypass**: Try to book more classes than membership allows
3. **Expired Membership**: Book classes with expired membership
4. **Concurrent Bookings**: Multiple users booking last spot simultaneously
5. **Session Hijacking**: Test session security
6. **SQL Injection**: Try malicious inputs in forms
7. **XSS Attacks**: Try script injection in user inputs

## Test Data Cleanup

- [ ] Remove test users after testing
- [ ] Clean up test bookings
- [ ] Reset test membership records
- [ ] Clear test payment records

---

## Quick Test URLs

- Main booking page: `public/index.php`
- User registration: `public/register.php`
- User login: `public/login.php`
- Membership page: `public/user/membership.php`
- Video content: `public/user/videos.php`
- User dashboard: `public/user/dashboard.php`
- Test suite: `public/test_membership_web.php`
- Bug scanner: `public/bug_scan.php`

## Test User Credentials

For testing, you can create users with these credentials:

- **Email**: test1@example.com, **Password**: password123
- **Email**: test2@example.com, **Password**: password123
- **Email**: test3@example.com, **Password**: password123

## Notes

- Test in incognito/private browsing mode to avoid session conflicts
- Clear browser cache if experiencing unexpected behavior
- Check browser console for JavaScript errors
- Monitor server error logs during testing
- Test with different user roles and membership states
