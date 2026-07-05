import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import { 
  LayoutDashboard, Users, BookOpen, GraduationCap, 
  CalendarDays, FileText, ClipboardList, CreditCard, 
  Settings, LogOut, Bell
} from 'lucide-react';

const Sidebar = () => {
  const { user, logout, isAdmin, isStaff, isStudent } = useAuth();
  const location = useLocation();

  const links = [
    { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['admin', 'staff', 'student'] },
    { to: '/students', label: 'Students', icon: Users, roles: ['admin', 'staff'] },
    { to: '/staff', label: 'Staff Directory', icon: Users, roles: ['admin'] },
    { to: '/classes', label: 'Classes & Sections', icon: BookOpen, roles: ['admin', 'staff', 'student'] },
    { to: '/subjects', label: 'Subjects', icon: BookOpen, roles: ['admin', 'staff'] },
    { to: '/attendance', label: 'Attendance', icon: ClipboardList, roles: ['admin', 'staff', 'student'] },
    { to: '/marks', label: 'Marks & Results', icon: FileText, roles: ['admin', 'staff', 'student'] },
    { to: '/exams', label: 'Exam Schedule', icon: CalendarDays, roles: ['admin', 'staff', 'student'] },
    { to: '/fees', label: 'Fee Management', icon: CreditCard, roles: ['admin', 'staff', 'student'] },
    { to: '/notifications', label: 'Notifications', icon: Bell, roles: ['admin', 'staff'] },
    { to: '/institute', label: 'Institute Profile', icon: Settings, roles: ['admin'] },
    { to: '/users', label: 'User Management', icon: Users, roles: ['admin'] },
  ];

  return (
    <aside className="w-64 bg-sidebar border-r border-surface-border hidden md:flex flex-col h-full sticky top-0">
      <div className="h-16 flex items-center px-6 border-b border-surface-border">
        <GraduationCap className="w-8 h-8 text-indigo-500 mr-3" />
        <span className="text-xl font-bold text-white tracking-tight">Tech Vision</span>
      </div>

      <div className="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        {links.map((link) => {
          if (!link.roles.includes(user?.role)) return null;
          const isActive = location.pathname.startsWith(link.to);
          const Icon = link.icon;
          return (
            <Link
              key={link.to}
              to={link.to}
              className={`sidebar-link ${isActive ? 'active' : ''}`}
            >
              <Icon />
              {link.label}
            </Link>
          );
        })}
      </div>

      <div className="p-4 border-t border-surface-border bg-slate-900/50">
        <div className="flex items-center gap-3 mb-4 px-2">
          <div className="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
            {user?.name?.charAt(0).toUpperCase()}
          </div>
          <div className="overflow-hidden">
            <p className="text-sm font-medium text-white truncate">{user?.name}</p>
            <p className="text-xs text-slate-400 capitalize">{user?.role}</p>
          </div>
        </div>
        <button 
          onClick={logout}
          className="w-full btn-ghost text-red-400 hover:text-red-300 hover:bg-red-400/10 justify-start"
        >
          <LogOut className="w-4 h-4 mr-2" />
          Logout
        </button>
      </div>
    </aside>
  );
};

export default Sidebar;
