const fs = require('fs');
const path = require('path');

const pages = [
  'auth/RegisterPage',
  'auth/ForgotPasswordPage',
  'students/StudentsPage',
  'students/StudentAddPage',
  'students/StudentEditPage',
  'students/StudentDetailPage',
  'students/StudentImportPage',
  'students/ReportCardPage',
  'staff/StaffPage',
  'staff/StaffAddPage',
  'staff/StaffEditPage',
  'classes/ClassesPage',
  'classes/ClassAddPage',
  'classes/ClassEditPage',
  'classes/ClassAssignPage',
  'subjects/SubjectsPage',
  'marks/MarksPage',
  'marks/MarksAddPage',
  'attendance/AttendancePage',
  'attendance/AttendanceMarkPage',
  'fee/FeePage',
  'fee/FeeCategoriesPage',
  'fee/FeeStructuresPage',
  'fee/FeePaymentsPage',
  'fee/FeeReportPage',
  'exam/ExamsPage',
  'exam/ExamAddPage',
  'notifications/NotificationsPage',
  'notifications/NotificationAddPage',
  'notifications/MyNotificationsPage',
  'users/UsersPage',
  'users/ApprovalsPage',
  'profile/ProfilePage',
  'institute/InstitutePage',
  'email/EmailPage'
];

const basePath = path.join(__dirname, 'src', 'pages');

for (const page of pages) {
  const fullPath = path.join(basePath, page + '.jsx');
  const dir = path.dirname(fullPath);
  
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }

  const componentName = page.split('/').pop();

  const content = `const ${componentName} = () => {
  return (
    <div className="card p-6">
      <h1 className="text-2xl font-bold text-white mb-4">${componentName}</h1>
      <p className="text-slate-400">This module is under construction.</p>
    </div>
  );
};

export default ${componentName};
`;

  if (!fs.existsSync(fullPath)) {
    fs.writeFileSync(fullPath, content);
    console.log('Created', fullPath);
  }
}
