import { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { Bell, Menu, UserCircle } from 'lucide-react';
import { Link } from 'react-router-dom';

const Navbar = ({ onMenuClick }) => {
  const { user } = useAuth();
  
  return (
    <nav className="h-16 bg-surface-card/80 backdrop-blur-md border-b border-surface-border flex items-center justify-between px-4 sticky top-0 z-20">
      <div className="flex items-center">
        <button 
          onClick={onMenuClick}
          className="p-2 mr-3 rounded-lg text-slate-400 hover:bg-slate-700/50 md:hidden focus:outline-none"
        >
          <Menu className="w-5 h-5" />
        </button>
        
        <div className="hidden sm:block">
          <h2 className="text-sm font-medium text-slate-300">
            Welcome back, <span className="text-white font-semibold">{user?.name}</span>
          </h2>
        </div>
      </div>

      <div className="flex items-center gap-4">
        <Link to="/notifications/my" className="relative p-2 rounded-full text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors">
          <Bell className="w-5 h-5" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-red-500 border-2 border-surface-card"></span>
        </Link>
        
        <Link to="/profile" className="flex items-center gap-2 hover:opacity-80 transition-opacity">
          <UserCircle className="w-8 h-8 text-indigo-400" />
        </Link>
      </div>
    </nav>
  );
};

export default Navbar;
