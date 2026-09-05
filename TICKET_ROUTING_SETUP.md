# Ticket Routing Matrix - Setup Instructions

## Current Status

✅ All code files updated and backwards-compatible  
⚠️ **Database migration NOT yet run** - you need to run it!

## What's Been Fixed

1. **Sidebar Navigation** - Added "Submit a Ticket" for students
2. **Backwards Compatibility** - Code now works BEFORE and AFTER migration
3. **Database Error** - Fixed duplicate table reference bug

## Step 1: Run the Migration

**IMPORTANT: Backup your database first!**

```bash
# Backup (Windows CMD)
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump" -u root -p edutrack > edutrack_backup.sql

# Run migration
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql" -u root -p edutrack < database\migration_2026_tickets_routing_matrix.sql
```

The migration will:
- Add `recipient_type` column (ENUM: 'admin', 'teacher', 'student')
- Add `recipient_id` column (foreign key to users table)
- Add 'Missing Activity' to category ENUM
- Create performance indexes
- Set up foreign key constraints

## Step 2: Test the Features

### Test 1: Student → Admin
1. Login as a student
2. Click **"Submit a Ticket"** in sidebar
3. Select **"Send to Admin"** radio button
4. Fill in subject/message → Submit
5. **Expected**: Ticket appears in Admin's "Tickets" page

### Test 2: Student → Teacher
1. Login as a student
2. Click **"Submit a Ticket"**
3. Select **"Send to My Instructor"** radio button
4. Choose a teacher from dropdown
5. Fill in subject/message → Submit
6. Login as that teacher
7. Click **"My Tickets"**
8. **Expected**: Ticket appears in **"Messages from Students"** section

### Test 3: Teacher → Admin
1. Login as a teacher
2. Click **"Submit a Ticket"**
3. Fill in subject/message → Submit
4. Login as admin
5. **Expected**: Ticket appears in Admin's "Tickets" page

### Test 4: Teacher → Student
1. Login as a teacher
2. Click **"Message a Student"** in sidebar
3. Select a student from dropdown (only YOUR students shown)
4. Select category: Grade Concern / Missing Activity / Other
5. Fill in subject/message → Submit
6. Login as that student
7. Click **"My Tickets"**
8. **Expected**: Ticket appears with **"→You"** badge (received message)

## Step 3: Verify IDOR Protection

### Test: Teacher Cannot Message Non-Student
1. Login as a teacher
2. Try to message a student NOT in their sections
3. **Expected**: Student won't appear in dropdown (client-side)
4. If you bypass client validation (e.g., browser dev tools), server should reject with error

### Test: Student Cannot Message Wrong Teacher
1. Login as a student
2. Try to submit ticket to a teacher NOT teaching them
3. **Expected**: Teacher won't appear in dropdown (client-side)
4. If you bypass client validation, server should reject with error

## Sidebar Navigation Reference

### Admin
- Dashboard
- Academic Setup (collapsible)
  - Semesters
  - Strands
  - Sections
- Manage Users
- Reports
- Announcements
- **Tickets** ← All tickets with recipient_type='admin'
- Grade Submission Status
- Activity Log

### Teacher
- Dashboard
- My Subjects
- Encode Grades
- Reports
- **Submit a Ticket** ← To Admin only
- **My Tickets** ← 3 sections:
  - My Tickets to Admin
  - Messages from Students
  - Messages I Sent to Students
- **Message a Student** ← Send to specific student

### Student
- Dashboard
- **Submit a Ticket** ← To Admin OR Teacher
- **My Tickets** ← Sent + Received tickets

## Routing Matrix (Final)

| From    | To      | Allowed? | UI Location                    |
|---------|---------|----------|--------------------------------|
| Student | Admin   | ✅ YES   | Submit a Ticket → "Send to Admin" |
| Student | Teacher | ✅ YES   | Submit a Ticket → "Send to My Instructor" |
| Student | Student | ❌ NO    | Not implemented |
| Teacher | Admin   | ✅ YES   | Submit a Ticket |
| Teacher | Student | ✅ YES   | Message a Student |
| Teacher | Teacher | ❌ NO    | Not implemented |
| Admin   | Student | ❌ NO    | Admin replies via ticket thread |
| Admin   | Teacher | ❌ NO    | Admin replies via ticket thread |
| Admin   | Admin   | ❌ NO    | Not needed |

## Troubleshooting

### "Column 'recipient_type' not found"
**Solution**: Run the migration SQL file (Step 1)

### "Tickets not showing in Admin view"
**Problem**: Admin query filters by `recipient_type='admin'`  
**Solution**: After migration, only tickets submitted TO admin will show. Old tickets without recipient_type will not appear in admin view unless you update them manually:

```sql
-- Update old tickets to have recipient_type='admin'
UPDATE tickets SET recipient_type = 'admin' WHERE recipient_type IS NULL;
```

### "Student tickets disappeared"
**Solution**: They're now split by direction. Check both:
- Tickets YOU sent (with "You→" badge)
- Tickets sent TO YOU (with "→You" badge)

### "Teacher sees no student messages"
**Problem**: Students haven't submitted tickets to teachers yet  
**Test**: Login as a student, submit ticket with "Send to My Instructor" option

## Files Modified

- `application/models/Ticket_model.php` - Backwards compatible queries
- `application/models/Enrollment_model.php` - IDOR validation methods
- `application/controllers/Student.php` - Recipient picker, inbox view
- `application/controllers/Teacher.php` - Message student, 3-section inbox
- `application/controllers/Admin.php` - No changes (already filtered correctly)
- `application/views/partials/sidebar.php` - Added student "Submit a Ticket"
- `application/views/student/*` - Direction badges, recipient picker
- `application/views/teacher/*` - 3-section inbox, message_student form
- `application/config/routes.php` - All routes registered
- `database/migration_2026_tickets_routing_matrix.sql` - Schema migration

## Next Steps

1. ✅ **Run the migration** (Step 1 above)
2. ✅ **Test all 4 routing paths** (Step 2)
3. ✅ **Verify IDOR protection** (Step 3)
4. 🎯 **You're done!**
