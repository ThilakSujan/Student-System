import { useState } from 'react';
import { toast } from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
// import { updateProfile } from '../../api/auth'; // Not in initial API files, mocking implementation

const ProfilePage = () => {
  const { user } = useAuth();
  const [form, setForm] = useState({
    full_name: user?.full_name || user?.student_name || '',
    phone: user?.phone || '',
    email: user?.email || '',
    current_password: '',
    new_password: '',
    confirm_password: ''
  });
  const [saving, setSaving] = useState(false);

  const set = (f) => (e) => setForm(p => ({ ...p, [f]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (form.new_password && form.new_password !== form.confirm_password) {
      toast.error('Passwords do not match');
      return;
    }
    setSaving(true);
    // Simulate API call
    setTimeout(() => {
      toast.success('Profile updated successfully (Simulated)');
      setForm(p => ({ ...p, current_password: '', new_password: '', confirm_password: '' }));
      setSaving(false);
    }, 800);
  };

  const initials = (form.full_name || user?.username || '?').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl">
      <div>
        <h1 className="page-title">My Profile</h1>
        <p className="page-subtitle">Manage your account settings</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="md:col-span-1">
          <div className="card p-6 flex flex-col items-center text-center">
            <div className="w-32 h-32 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-4xl font-bold mb-4 shadow-lg shadow-indigo-500/20">
              {initials}
            </div>
            <h2 className="text-xl font-bold text-white">{form.full_name || user?.username}</h2>
            <p className="text-indigo-400 capitalize mb-2">{user?.role}</p>
            <p className="text-slate-400 text-sm mb-4">{user?.email}</p>
            <span className="badge badge-success">Active Account</span>
          </div>
        </div>

        <div className="md:col-span-2">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="card">
              <div className="card-header"><h3 className="section-title">Personal Information</h3></div>
              <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="form-group">
                  <label className="label">Full Name</label>
                  <input className="input" value={form.full_name} onChange={set('full_name')} />
                </div>
                <div className="form-group">
                  <label className="label">Email Address</label>
                  <input className="input" value={form.email} readOnly disabled title="Contact admin to change email" />
                </div>
                <div className="form-group">
                  <label className="label">Phone Number</label>
                  <input className="input" value={form.phone} onChange={set('phone')} />
                </div>
                {user?.role === 'student' && (
                  <div className="form-group">
                    <label className="label">Department / Stream</label>
                    <input className="input" value={user?.department || '—'} readOnly disabled />
                  </div>
                )}
              </div>
            </div>

            <div className="card">
              <div className="card-header"><h3 className="section-title">Change Password</h3></div>
              <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="form-group sm:col-span-2">
                  <label className="label">Current Password</label>
                  <input type="password" className="input max-w-sm" placeholder="••••••••" value={form.current_password} onChange={set('current_password')} />
                </div>
                <div className="form-group">
                  <label className="label">New Password</label>
                  <input type="password" className="input" placeholder="••••••••" value={form.new_password} onChange={set('new_password')} />
                </div>
                <div className="form-group">
                  <label className="label">Confirm New Password</label>
                  <input type="password" className="input" placeholder="••••••••" value={form.confirm_password} onChange={set('confirm_password')} />
                </div>
              </div>
            </div>

            <div className="flex justify-end gap-3">
              <button type="submit" className="btn btn-primary px-8" disabled={saving}>
                {saving ? 'Saving...' : 'Save Changes'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default ProfilePage;