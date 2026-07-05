import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider, useAuth } from './context/AuthContext';

// Layout
import Layout from './components/layout/Layout';

// Auth Pages
import LoginPage from './pages/auth/LoginPage';
import RegisterPage from './pages/auth/RegisterPage';
import ForgotPasswordPage from './pages/auth/ForgotPasswordPage';

// Dashboard
import DashboardPage from './pages/dashboard/DashboardPage';

// Students
import StudentsPage from './pages/students/StudentsPage';
import StudentAddPage from './pages/students/StudentAddPage';
import StudentEditPage from './pages/students/StudentEditPage';
import StudentDetailPage from './pages/students/StudentDetailPage';
import StudentImportPage from './pages/students/StudentImportPage';
import ReportCardPage from './pages/students/ReportCardPage';

// Staff
import StaffPage from './pages/staff/StaffPage';
import StaffAddPage from './pages/staff/StaffAddPage';
import StaffEditPage from './pages/staff/StaffEditPage';

// Classes
import ClassesPage from './pages/classes/ClassesPage';
import ClassAddPage from './pages/classes/ClassAddPage';
import ClassEditPage from './pages/classes/ClassEditPage';
import ClassAssignPage from './pages/classes/ClassAssignPage';

// Subjects
import SubjectsPage from './pages/subjects/SubjectsPage';

// Marks
import MarksPage from './pages/marks/MarksPage';
import MarksAddPage from './pages/marks/MarksAddPage';

// Attendance
import AttendancePage from './pages/attendance/AttendancePage';
import AttendanceMarkPage from './pages/attendance/AttendanceMarkPage';

// Fee
import FeePage from './pages/fee/FeePage';
import FeeCategoriesPage from './pages/fee/FeeCategoriesPage';
import FeeStructuresPage from './pages/fee/FeeStructuresPage';
import FeePaymentsPage from './pages/fee/FeePaymentsPage';
import FeeReportPage from './pages/fee/FeeReportPage';

// Exam
import ExamsPage from './pages/exam/ExamsPage';
import ExamAddPage from './pages/exam/ExamAddPage';

// Notifications
import NotificationsPage from './pages/notifications/NotificationsPage';
import NotificationAddPage from './pages/notifications/NotificationAddPage';
import MyNotificationsPage from './pages/notifications/MyNotificationsPage';

// Users (Admin Panel)
import UsersPage from './pages/users/UsersPage';
import ApprovalsPage from './pages/users/ApprovalsPage';

// Profile & Institute
import ProfilePage from './pages/profile/ProfilePage';
import InstitutePage from './pages/institute/InstitutePage';

// Email
import EmailPage from './pages/email/EmailPage';

// ─── Protected Route ─────────────────────────────────────────────────────────
const ProtectedRoute = ({ children, roles }) => {
  const { isAuthenticated, user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-950 flex items-center justify-center">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-slate-400">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) return <Navigate to="/login" replace />;
  if (roles && !roles.includes(user?.role)) return <Navigate to="/dashboard" replace />;
  return children;
};

// ─── Public Route (redirect if logged in) ───────────────────────────────────
const PublicRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();
  if (loading) return null;
  return isAuthenticated ? <Navigate to="/dashboard" replace /> : children;
};

import StudentLoginPage from './pages/auth/StudentLoginPage';

// ─── App Router ──────────────────────────────────────────────────────────────
function AppRouter() {
  return (
    <Routes>
      {/* Public Routes */}
      <Route path="/login" element={<PublicRoute><LoginPage /></PublicRoute>} />
      <Route path="/student-login" element={<PublicRoute><StudentLoginPage /></PublicRoute>} />
      <Route path="/register" element={<PublicRoute><RegisterPage /></PublicRoute>} />
      <Route path="/forgot-password" element={<PublicRoute><ForgotPasswordPage /></PublicRoute>} />

      {/* Protected Routes (inside Layout) */}
      <Route path="/" element={<ProtectedRoute><Layout /></ProtectedRoute>}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<DashboardPage />} />

        {/* Students */}
        <Route path="students" element={<ProtectedRoute roles={['admin','staff']}><StudentsPage /></ProtectedRoute>} />
        <Route path="students/add" element={<ProtectedRoute roles={['admin','staff']}><StudentAddPage /></ProtectedRoute>} />
        <Route path="students/:id/edit" element={<ProtectedRoute roles={['admin','staff']}><StudentEditPage /></ProtectedRoute>} />
        <Route path="students/:id" element={<ProtectedRoute roles={['admin','staff']}><StudentDetailPage /></ProtectedRoute>} />
        <Route path="students/import" element={<ProtectedRoute roles={['admin','staff']}><StudentImportPage /></ProtectedRoute>} />
        <Route path="students/:id/report-card" element={<ProtectedRoute roles={['admin','staff']}><ReportCardPage /></ProtectedRoute>} />

        {/* Staff (Admin only) */}
        <Route path="staff" element={<ProtectedRoute roles={['admin']}><StaffPage /></ProtectedRoute>} />
        <Route path="staff/add" element={<ProtectedRoute roles={['admin']}><StaffAddPage /></ProtectedRoute>} />
        <Route path="staff/:id/edit" element={<ProtectedRoute roles={['admin']}><StaffEditPage /></ProtectedRoute>} />

        {/* Classes */}
        <Route path="classes" element={<ProtectedRoute roles={['admin','staff']}><ClassesPage /></ProtectedRoute>} />
        <Route path="classes/add" element={<ProtectedRoute roles={['admin']}><ClassAddPage /></ProtectedRoute>} />
        <Route path="classes/:id/edit" element={<ProtectedRoute roles={['admin']}><ClassEditPage /></ProtectedRoute>} />
        <Route path="classes/:id/assign" element={<ProtectedRoute roles={['admin','staff']}><ClassAssignPage /></ProtectedRoute>} />

        {/* Subjects */}
        <Route path="subjects" element={<ProtectedRoute roles={['admin','staff']}><SubjectsPage /></ProtectedRoute>} />

        {/* Marks */}
        <Route path="marks" element={<MarksPage />} />
        <Route path="marks/add" element={<ProtectedRoute roles={['admin','staff']}><MarksAddPage /></ProtectedRoute>} />

        {/* Attendance */}
        <Route path="attendance" element={<AttendancePage />} />
        <Route path="attendance/mark" element={<ProtectedRoute roles={['admin','staff']}><AttendanceMarkPage /></ProtectedRoute>} />

        {/* Fee */}
        <Route path="fees" element={<FeePage />} />
        <Route path="fees/categories" element={<ProtectedRoute roles={['admin']}><FeeCategoriesPage /></ProtectedRoute>} />
        <Route path="fees/structures" element={<ProtectedRoute roles={['admin']}><FeeStructuresPage /></ProtectedRoute>} />
        <Route path="fees/payments" element={<ProtectedRoute roles={['admin','staff']}><FeePaymentsPage /></ProtectedRoute>} />
        <Route path="fees/report" element={<ProtectedRoute roles={['admin','staff']}><FeeReportPage /></ProtectedRoute>} />

        {/* Exam */}
        <Route path="exams" element={<ExamsPage />} />
        <Route path="exams/add" element={<ProtectedRoute roles={['admin','staff']}><ExamAddPage /></ProtectedRoute>} />

        {/* Notifications */}
        <Route path="notifications" element={<ProtectedRoute roles={['admin','staff']}><NotificationsPage /></ProtectedRoute>} />
        <Route path="notifications/add" element={<ProtectedRoute roles={['admin','staff']}><NotificationAddPage /></ProtectedRoute>} />
        <Route path="notifications/my" element={<MyNotificationsPage />} />

        {/* Admin: Users & Approvals */}
        <Route path="users" element={<ProtectedRoute roles={['admin']}><UsersPage /></ProtectedRoute>} />
        <Route path="approvals" element={<ProtectedRoute roles={['admin']}><ApprovalsPage /></ProtectedRoute>} />

        {/* Profile & Institute */}
        <Route path="profile" element={<ProfilePage />} />
        <Route path="institute" element={<ProtectedRoute roles={['admin']}><InstitutePage /></ProtectedRoute>} />
        <Route path="email" element={<ProtectedRoute roles={['admin']}><EmailPage /></ProtectedRoute>} />
      </Route>

      {/* Catch-all */}
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <AppRouter />
        <Toaster
          position="top-right"
          toastOptions={{
            style: {
              background: '#1e293b',
              color: '#f1f5f9',
              border: '1px solid rgba(99,102,241,0.3)',
              borderRadius: '12px',
              fontSize: '14px',
            },
            success: {
              iconTheme: { primary: '#10b981', secondary: '#1e293b' },
            },
            error: {
              iconTheme: { primary: '#ef4444', secondary: '#1e293b' },
            },
            duration: 4000,
          }}
        />
      </AuthProvider>
    </BrowserRouter>
  );
}

export default App;
