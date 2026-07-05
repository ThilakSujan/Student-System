import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { getDashboardStats } from '../../api/dashboard';

// ─── Icon Components ──────────────────────────────────────────────────────────
const UsersIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
  </svg>
);
const AcademicCapIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
  </svg>
);
const CalendarIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
  </svg>
);
const CurrencyIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
  </svg>
);
const BriefcaseIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-6 h-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
  </svg>
);
const ArrowUpRightIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 19.5 15-15m0 0H8.25m11.25 0v11.25" />
  </svg>
);

// ─── Stat Card ────────────────────────────────────────────────────────────────
const StatCard = ({ title, value, subtitle, icon, gradient, linkTo, linkLabel, loading }) => (
  <div className="card p-5 flex flex-col gap-4 hover:border-indigo-500/30 transition-all duration-300 hover:-translate-y-0.5">
    <div className="flex items-start justify-between">
      <div className={`w-12 h-12 rounded-xl flex items-center justify-center text-white flex-shrink-0 ${gradient}`}>
        {icon}
      </div>
      {linkTo && (
        <Link to={linkTo} className="text-slate-500 hover:text-indigo-400 transition-colors">
          <ArrowUpRightIcon />
        </Link>
      )}
    </div>
    {loading ? (
      <>
        <div className="h-8 w-20 bg-slate-700/50 animate-pulse rounded" />
        <div className="h-4 w-32 bg-slate-700/50 animate-pulse rounded" />
      </>
    ) : (
      <>
        <div>
          <p className="text-3xl font-bold text-white">{value}</p>
          <p className="text-sm font-medium text-slate-300 mt-0.5">{title}</p>
        </div>
        {subtitle && <p className="text-xs text-slate-500">{subtitle}</p>}
      </>
    )}
  </div>
);

// ─── Quick Action Card ────────────────────────────────────────────────────────
const QuickAction = ({ to, icon, label, desc, color }) => (
  <Link
    to={to}
    className="card p-4 flex items-center gap-4 hover:border-indigo-500/30 transition-all duration-200 hover:-translate-y-0.5 group"
  >
    <div className={`w-10 h-10 rounded-lg flex items-center justify-center text-white flex-shrink-0 ${color}`}>
      {icon}
    </div>
    <div>
      <p className="text-sm font-semibold text-white group-hover:text-indigo-300 transition-colors">{label}</p>
      <p className="text-xs text-slate-500">{desc}</p>
    </div>
    <ArrowUpRightIcon />
  </Link>
);

// ─── Progress Bar ─────────────────────────────────────────────────────────────
const ProgressBar = ({ value, max = 100, color = 'bg-indigo-500' }) => {
  const pct = Math.min(100, Math.round((value / max) * 100));
  return (
    <div className="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
      <div
        className={`h-2 rounded-full transition-all duration-700 ${color}`}
        style={{ width: `${pct}%` }}
      />
    </div>
  );
};

// ─── Main Dashboard Page ──────────────────────────────────────────────────────
const DashboardPage = () => {
  const { user, isAdmin, isAdminOrStaff } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getDashboardStats()
      .then((res) => setStats(res.data.data))
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
  });

  return (
    <div className="space-y-6 animate-fade-in">
      {/* ── Header ── */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 className="page-title">
            Good {new Date().getHours() < 12 ? 'Morning' : new Date().getHours() < 17 ? 'Afternoon' : 'Evening'},{' '}
            <span className="gradient-text">{user?.username || 'User'}!</span>
          </h1>
          <p className="page-subtitle">{today}</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="badge badge-success text-xs">● System Online</span>
        </div>
      </div>

      {/* ── Admin / Staff Stats ── */}
      {isAdminOrStaff && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            loading={loading}
            title="Active Students"
            value={stats?.totalStudents ?? '—'}
            subtitle="Currently enrolled"
            icon={<UsersIcon />}
            gradient="bg-gradient-to-br from-indigo-500 to-indigo-700"
            linkTo="/students"
            linkLabel="View all"
          />
          <StatCard
            loading={loading}
            title="Active Classes"
            value={stats?.totalClasses ?? '—'}
            subtitle="Running this term"
            icon={<AcademicCapIcon />}
            gradient="bg-gradient-to-br from-emerald-500 to-emerald-700"
            linkTo="/classes"
          />
          <StatCard
            loading={loading}
            title="Today's Attendance"
            value={stats ? `${stats.attendancePercentage}%` : '—'}
            subtitle="Present today"
            icon={<CalendarIcon />}
            gradient="bg-gradient-to-br from-amber-500 to-amber-700"
            linkTo="/attendance"
          />
          {isAdmin && (
            <StatCard
              loading={loading}
              title="Monthly Revenue"
              value={stats ? `₹${(stats.monthlyRevenue || 0).toLocaleString()}` : '—'}
              subtitle="Fee collected this month"
              icon={<CurrencyIcon />}
              gradient="bg-gradient-to-br from-pink-500 to-rose-700"
              linkTo="/fees"
            />
          )}
          {!isAdmin && (
            <StatCard
              loading={loading}
              title="Staff Members"
              value={stats?.totalStaff ?? '—'}
              subtitle="Approved accounts"
              icon={<BriefcaseIcon />}
              gradient="bg-gradient-to-br from-purple-500 to-purple-700"
              linkTo="/staff"
            />
          )}
        </div>
      )}

      {/* ── Attendance Visual (Admin/Staff) ── */}
      {isAdminOrStaff && !loading && stats && (
        <div className="card p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="section-title">Today's Attendance Overview</h2>
            <Link to="/attendance" className="btn btn-ghost btn-sm text-indigo-400 hover:text-indigo-300">
              View Details →
            </Link>
          </div>
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <div className="flex justify-between text-sm mb-1">
                <span className="text-slate-400">Students Present</span>
                <span className="text-white font-semibold">{stats.attendancePercentage}%</span>
              </div>
              <ProgressBar value={parseFloat(stats.attendancePercentage)} color="bg-emerald-500" />
            </div>
            <div className="text-right">
              <p className="text-2xl font-bold text-emerald-400">{stats.attendancePercentage}%</p>
              <p className="text-xs text-slate-500">Attendance Rate</p>
            </div>
          </div>
        </div>
      )}

      {/* ── Two-column section: Quick Actions + Summary ── */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick Actions */}
        {isAdminOrStaff && (
          <div className="lg:col-span-2 card p-6">
            <h2 className="section-title mb-4">Quick Actions</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <QuickAction
                to="/students/add"
                label="Add New Student"
                desc="Enroll a new student"
                color="bg-indigo-600"
                icon={<UsersIcon />}
              />
              <QuickAction
                to="/attendance/mark"
                label="Mark Attendance"
                desc="Record today's attendance"
                color="bg-emerald-600"
                icon={<CalendarIcon />}
              />
              <QuickAction
                to="/marks/add"
                label="Enter Marks"
                desc="Record exam scores"
                color="bg-amber-600"
                icon={<AcademicCapIcon />}
              />
              <QuickAction
                to="/fees/payments"
                label="Record Payment"
                desc="Log a fee payment"
                color="bg-pink-600"
                icon={<CurrencyIcon />}
              />
              {isAdmin && (
                <QuickAction
                  to="/staff/add"
                  label="Add Staff Member"
                  desc="Create a staff account"
                  color="bg-purple-600"
                  icon={<BriefcaseIcon />}
                />
              )}
              {isAdmin && (
                <QuickAction
                  to="/approvals"
                  label="Pending Approvals"
                  desc="Review new registrations"
                  color="bg-rose-600"
                  icon={<BriefcaseIcon />}
                />
              )}
            </div>
          </div>
        )}

        {/* System Info */}
        <div className="card p-6 flex flex-col gap-4">
          <h2 className="section-title">System Info</h2>
          <div className="space-y-3">
            {[
              { label: 'Platform', value: 'MERN Stack' },
              { label: 'Database', value: 'MongoDB' },
              { label: 'Backend', value: 'Node.js / Express' },
              { label: 'Frontend', value: 'React + Vite' },
              { label: 'Your Role', value: user?.role?.charAt(0).toUpperCase() + user?.role?.slice(1) || '—' },
              { label: 'Account', value: user?.account_status || '—' },
            ].map(({ label, value }) => (
              <div key={label} className="flex justify-between items-center text-sm">
                <span className="text-slate-500">{label}</span>
                <span className="text-slate-300 font-medium">{value}</span>
              </div>
            ))}
          </div>
          <div className="pt-3 border-t border-slate-700/50">
            <div className="flex items-center gap-2">
              <div className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
              <span className="text-xs text-slateald-400">All systems operational</span>
            </div>
          </div>
        </div>
      </div>

      {/* ── Student View ── */}
      {!isAdminOrStaff && !loading && stats && (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <StatCard
            loading={false}
            title="Enrolled Classes"
            value={stats.enrolledClasses ?? 0}
            subtitle="Currently attending"
            icon={<AcademicCapIcon />}
            gradient="bg-gradient-to-br from-indigo-500 to-indigo-700"
            linkTo="/classes"
          />
          <StatCard
            loading={false}
            title="My Attendance"
            value={`${stats.attendancePercentage}%`}
            subtitle="Overall attendance rate"
            icon={<CalendarIcon />}
            gradient="bg-gradient-to-br from-emerald-500 to-emerald-700"
            linkTo="/attendance"
          />
        </div>
      )}
    </div>
  );
};

export default DashboardPage;
