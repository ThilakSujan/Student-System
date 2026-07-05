import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { createStaff } from '../../api/staff';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const StaffAddPage = () => {
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    username: '',
    email: '',
    password: '',
    confirm_password: '',
    full_name: '',
    phone: '',
  });

  const set = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const errs = {};
    if (!form.username.trim()) errs.username = 'Username is required';
    if (!form.email.trim()) errs.email = 'Email is required';
    else if (!/\S+@\S+\.\S+/.test(form.email)) errs.email = 'Enter a valid email';
    if (!form.password) errs.password = 'Password is required';
    else if (form.password.length < 6) errs.password = 'Password must be at least 6 characters';
    if (form.password !== form.confirm_password) errs.confirm_password = 'Passwords do not match';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const payload = {
        username: form.username,
        email: form.email,
        password: form.password,
        full_name: form.full_name || undefined,
        phone: form.phone || undefined,
      };
      await createStaff(payload);
      toast.success('Staff member added successfully!');
      navigate('/staff');
    } catch (err) {
      const msg = err.response?.data?.message || 'Failed to add staff member';
      toast.error(msg);
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <div className="flex items-center gap-4">
        <Link to="/staff" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Add Staff Member</h1>
          <p className="page-subtitle">Create a new staff account</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="card">
          <div className="card-header">
            <h2 className="section-title">Account Information</h2>
          </div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="username" className="label">Username *</label>
              <input id="username" className={`input ${errors.username ? 'input-error' : ''}`} placeholder="e.g. jdoe" value={form.username} onChange={set('username')} />
              {errors.username && <p className="error-msg">{errors.username}</p>}
            </div>

            <div className="form-group">
              <label htmlFor="email" className="label">Email Address *</label>
              <input id="email" type="email" className={`input ${errors.email ? 'input-error' : ''}`} placeholder="staff@example.com" value={form.email} onChange={set('email')} />
              {errors.email && <p className="error-msg">{errors.email}</p>}
            </div>

            <div className="form-group">
              <label htmlFor="password" className="label">Password *</label>
              <input id="password" type="password" className={`input ${errors.password ? 'input-error' : ''}`} placeholder="••••••••" value={form.password} onChange={set('password')} />
              {errors.password && <p className="error-msg">{errors.password}</p>}
            </div>

            <div className="form-group">
              <label htmlFor="confirm_password" className="label">Confirm Password *</label>
              <input id="confirm_password" type="password" className={`input ${errors.confirm_password ? 'input-error' : ''}`} placeholder="••••••••" value={form.confirm_password} onChange={set('confirm_password')} />
              {errors.confirm_password && <p className="error-msg">{errors.confirm_password}</p>}
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header">
            <h2 className="section-title">Personal Information (Optional)</h2>
          </div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="full_name" className="label">Full Name</label>
              <input id="full_name" className="input" placeholder="e.g. John Doe" value={form.full_name} onChange={set('full_name')} />
            </div>
            <div className="form-group">
              <label htmlFor="phone" className="label">Phone Number</label>
              <input id="phone" className="input" placeholder="e.g. 9876543210" value={form.phone} onChange={set('phone')} />
            </div>
          </div>
        </div>

        <div className="flex gap-3 justify-end">
          <Link to="/staff" className="btn btn-secondary">Cancel</Link>
          <button id="submit-staff" type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? (
              <><span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> Saving…</>
            ) : 'Add Staff Member'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default StaffAddPage;