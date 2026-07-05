import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getStaffMember, updateStaff } from '../../api/staff';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const StaffEditPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    username: '',
    email: '',
    full_name: '',
    phone: '',
    account_status: 'Approved',
  });

  useEffect(() => {
    getStaffMember(id)
      .then(res => {
        const s = res.data.data;
        setForm({
          username: s.username || '',
          email: s.email || '',
          full_name: s.full_name || '',
          phone: s.phone || '',
          account_status: s.account_status || 'Approved',
        });
      })
      .catch(() => toast.error('Failed to load staff member'))
      .finally(() => setLoading(false));
  }, [id]);

  const set = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const errs = {};
    if (!form.email.trim()) errs.email = 'Email is required';
    else if (!/\S+@\S+\.\S+/.test(form.email)) errs.email = 'Enter a valid email';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const payload = {
        email: form.email,
        full_name: form.full_name || undefined,
        phone: form.phone || undefined,
        account_status: form.account_status,
      };
      await updateStaff(id, payload);
      toast.success('Staff member updated successfully!');
      navigate('/staff');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to update staff member');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <div className="flex items-center gap-4">
        <Link to="/staff" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Edit Staff Member</h1>
          <p className="page-subtitle">Update staff account details</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="card">
          <div className="card-header">
            <h2 className="section-title">Account Information</h2>
          </div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="username" className="label">Username</label>
              <input id="username" className="input bg-slate-800/50 text-slate-400" value={form.username} readOnly disabled />
              <p className="text-xs text-slate-500 mt-1">Username cannot be changed</p>
            </div>

            <div className="form-group">
              <label htmlFor="email" className="label">Email Address *</label>
              <input id="email" type="email" className={`input ${errors.email ? 'input-error' : ''}`} value={form.email} onChange={set('email')} />
              {errors.email && <p className="error-msg">{errors.email}</p>}
            </div>

            <div className="form-group sm:col-span-2">
              <label htmlFor="account_status" className="label">Account Status</label>
              <select id="account_status" className="input" value={form.account_status} onChange={set('account_status')}>
                <option value="Approved">Approved</option>
                <option value="Suspended">Suspended</option>
              </select>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header">
            <h2 className="section-title">Personal Information</h2>
          </div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="full_name" className="label">Full Name</label>
              <input id="full_name" className="input" value={form.full_name} onChange={set('full_name')} />
            </div>
            <div className="form-group">
              <label htmlFor="phone" className="label">Phone Number</label>
              <input id="phone" className="input" value={form.phone} onChange={set('phone')} />
            </div>
          </div>
        </div>

        <div className="flex gap-3 justify-end">
          <Link to="/staff" className="btn btn-secondary">Cancel</Link>
          <button id="submit-staff" type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? (
              <><span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> Saving…</>
            ) : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default StaffEditPage;