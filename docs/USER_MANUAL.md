# CLO Document Tracking System
## User Manual

## 1. Getting Started

The system is available through the internal LAN. Open the CLO system address in a browser, then sign in using your username/email and password.

### Login

1. Open the CLO login page.
2. Enter your username or email.
3. Enter your password.
4. Select Remember Me only on a trusted workstation.
5. Click Sign In.

After a successful login, the dashboard opens. The sidebar and dashboard shortcuts show only pages assigned to your role. Use **Access Requests** to follow your confidential-document requests or review requests for documents assigned to you.

## 2. User Roles

- **ADMIN**: full system administration and configuration.
- **CITY_ATTORNEY**: legal review and authorized document processing.
- **LEGAL_STAFF**: document processing and drafting.
- **RECORDS_OFFICER**: receiving, routing, monitoring, and archiving.
- **DOCUMENT_VIEWER**: dashboard and tracking/viewing access assigned by the administrator.
- **INCOMING**: receives, creates, assigns, and imports incoming documents.
- **OUTGOING**: handles routing documents to other departments.

Your available menu depends on your role. Do not share your password or browser session.

### Quick Role Guide

- **ADMIN** manages users, roles, settings, and system-wide document actions.
- **CITY_ATTORNEY** and **LEGAL_STAFF** process legal documents and may route documents to a CLO user.
- **RECORDS_OFFICER** handles records and archive tasks; routing from this role is limited to CLO users.
- **INCOMING** handles incoming documents and assignments; routing from this role is limited to CLO users.
- **OUTGOING** routes assigned documents to other departments.
- **DOCUMENT_VIEWER** is for viewing and tracking only.

Only **ADMIN** and **OUTGOING** can route documents to another department. Other routing roles can route only to a staff member in City Legal Office. Document assignments and confidentiality permissions still control what content each user can see.

## 3. Dashboard

The dashboard may show:

- Total documents
- Pending review
- Approved documents
- Urgent or overdue documents
- Document volume trends
- Documents by type
- Weekly activity
- Recent activity
- Recent documents
- Quick actions

Click a card or quick action to open the related module. If a page is not assigned to your role, it will not appear in the menu.

## 4. Receiving a Document

For users with Document Management access:

1. Open **Document Management**.
2. Choose the receive/new-document action.
3. Enter the document title and description.
4. Select the document type.
5. Select the originating and current department.
6. Set priority and confidentiality.
7. Enter received and due dates when applicable.
8. Select the current assignee if required.
9. Upload the document file.
10. Submit the form.
11. Record the generated tracking number for future searches.

Use the tracking number whenever following up on the document.

## 5. Updating a Document

1. Open **Document Management**.
2. Search or filter the document.
3. Open the document action.
4. Update only the fields that changed.
5. Upload a replacement file only when a new file version is required.
6. Save the update.

Keep the original file name and document information clear enough for later retrieval.

## 6. Routing a Document

For users with Document Routing access:

1. Open **Document Routing**.
2. Find the document.
3. Select the destination department. CITY_ATTORNEY, LEGAL_STAFF, RECORDS_OFFICER, and INCOMING can select City Legal Office only. ADMIN and OUTGOING can select other departments.
4. Select a recipient when applicable. ADMIN and OUTGOING may type an external recipient name only when the destination department has no system users.
5. Add remarks explaining the required action.
6. Set a due date when applicable.
7. Click Send/Route.
8. Confirm that the success message and route status appear.

The recipient receives a notification. The route initially appears as pending. Routing a confidential document does not automatically grant access to its file contents.

### Requesting Confidential Document Access

1. Open the document in Document Management.
2. In File History, select **Request Access**.
3. Open **Access Requests** from the sidebar or dashboard to check its status.
4. The current assignee reviews the request. Approval grants file access; routing alone does not.

You may cancel your own pending request. Only the current assignee or an administrator can approve, reject, or revoke access.

## 7. Receiving a Routed Document

1. Open the notification bell when a route notification appears.
2. Click the document notification.
3. Document Routing opens.
4. Find the matching route in the list.
5. Open the receive action.
6. Add receiving remarks if needed.
6. Optionally attach a supporting PDF, DOC, or DOCX file (maximum 10 MB).
7. Confirm receipt.

Receiving a route updates the route status and records the received time.

## 8. Tracking a Document

1. Open **Document Tracking**.
2. Enter the tracking number.
3. Submit the search.
4. Review:
   - Current status
   - Current department
   - Current assignee
   - Date received
   - Due date
   - Route timeline
   - Previous offices and recipients

Public tracking only shows information allowed by the system's visibility rules.

## 9. Pending Documents

1. Open **Pending Documents**.
2. Review documents waiting for action or review.
3. Check overdue and urgent indicators.
4. Open the relevant document or route.
5. Complete the required processing or receiving action.

Use this page as a daily work queue.

## 10. Archiving and Restoring

### Archive

1. Open Document Management or the Archive workflow available to your role.
2. Find a completed document.
3. Confirm that it is ready for archive.
4. Select Archive.
5. Confirm the action.

### Restore

1. Open **Archive**.
2. Search for the archived document.
3. Select Restore.
4. Confirm the action.
5. Continue processing from Document Management if needed.

Do not archive a document that still requires routing or review.

## 11. Reports

1. Open **Reports**.
2. Select the report period.
3. Set the date or filters.
4. Review the result.
5. Use the available print/export action if provided.

Reports are based on document, status, routing, and activity data available to the system.

## 12. Notifications

The notification bell can show:

- Documents routed to you
- Documents received from you or by the related route
- Pending account approvals for administrators

Opening the notification menu does not clear notices. Click a document notification to open its related page; read notices remain in the recent list.

Open the **Messages** button to view conversations. A green dot beside a contact, or **Online** in the open conversation, means they have recently been active in the system. The status clears automatically after they leave.

In a conversation, choose **Delete** on a message to remove it just for you; on messages you sent, you can also choose to remove it for everyone. **Clear conversation** offers the same choices for the full conversation. “Just me” does not remove messages from the other participant's inbox.

## 13. My Profile

Open **My Profile** to:

- Update first name, last name, and email
- Change your password
- Upload or remove a profile picture
- Set password-recovery answers

### Setting Recovery Answers

Existing users must set these answers before using self-service password recovery:

1. Open My Profile.
2. Enter favorite color.
3. Enter favorite food.
4. Enter full birthdate.
5. Click Save Recovery Answers.

Answers are stored as hashes and are not displayed back to the user.

## 14. Forgot Password

### Regular Staff Users

1. Click Forgot Password on the login page.
2. Enter username or email.
3. Enter employee number.
4. Answer the randomly selected security question.
5. Open the generated reset link.
6. Enter and confirm the new password.
7. Log in with the new password.

After too many failed answers, reset attempts are temporarily locked.

### Admin Users

Admin accounts do not use self-service password reset. Contact IT or another authorized administrator for a manual reset.

## 15. Admin User Management

Administrators can:

- Add users
- Edit user details
- Assign roles and departments
- Approve pending accounts
- Activate or deactivate accounts
- Change a user's password
- Delete accounts when authorized

### Approving a Staff Account

1. Open **User Management**.
2. Filter by Pending.
3. Open the user record.
4. Review the role and department.
5. Approve/activate the account.
6. Inform the user that registration is approved.

Assign the least access needed for the user's work.

## 16. Role Management

Only administrators can open **Roles**.

1. Open Roles from the sidebar.
2. Find the role.
3. Click Edit.
4. Select the pages that role may access.
5. Ensure non-admin roles include `dashboard.php`.
6. Save access.

Users receive page access through their assigned role. Changes affect users with that role.

## 17. Department and Document-Type Management

Administrators can maintain:

- Active departments
- Department descriptions and codes
- Document types
- Type descriptions and retention settings

Avoid deactivating a department or document type that is still needed by active records unless the system workflow has been reviewed.

## 18. Audit Trail

Administrators can open **Audit Trail** to review:

- User actions
- Document actions
- Routing actions
- Password-reset events
- Account changes
- IP address and browser information when recorded

Use filters such as action, entity type, date range, and search text.

## 19. Daily Work Checklist

### Records Officer

- Check new documents.
- Confirm tracking numbers.
- Review pending routes.
- Receive incoming routes.
- Check overdue items.
- Archive completed records.

### Legal Staff or City Attorney

- Check notifications.
- Review assigned documents.
- Process pending work.
- Add remarks and status updates.
- Route documents to the next office.
- Confirm due dates.

### Administrator

- Review pending account approvals.
- Check role and department assignments.
- Review audit trail for unusual activity.
- Check system settings and backups.
- Confirm that role pages are appropriate.

## 20. Troubleshooting

### I cannot see a page

Your role may not have that page assigned. Contact an administrator.

### I can log in but return to the login page

The account may be inactive or the role may not have dashboard access. An administrator should check the user's status and role permissions.

### I cannot reset my password

Confirm that your employee number and username/email match. Existing users must first save recovery answers in My Profile. Admin accounts require manual assistance.

### A document does not appear

Check the tracking number, department, status, filters, and your role access. Ask the records officer or administrator to verify the document assignment.

### A routed document cannot be received

Confirm that the route is assigned to your user or department and that you have Document Routing access.

## 21. Security Rules

- Never share your password.
- Do not use Remember Me on shared computers.
- Do not download confidential files to unauthorized devices.
- Log out when finished.
- Report unexpected access, missing documents, or suspicious notifications to the administrator.
- Use clear and accurate remarks when routing or receiving documents.
