import { useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';
import { useAuth } from '../../context/AuthContext';
// import { getInstitute, updateInstitute } from '../../api/institute'; // Assuming API exists

const InstitutePage = () => {
  const { user } = useAuth();
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  
  const [form, setForm] = useState({
    institute_name: 'St. Xavier\'s High School',
    address: '123 Education Lane, Knowledge City, State - 400001',
    phone: '+91 9876543210',
    email: 'contact@stxaviers.edu',
    principal_name: 'Dr. Arthur Pendragon',
    other_details: 'Founded in 1995. Affiliated with CBSE.'
  });

  useEffect(() => {
    // In a real app, fetch institute data here
    // getInstitute().then(res => setForm(res.data.data)).finally(() => setLoading(false));
  }, []);

  const set = (f) => (e) => setForm(p => ({ ...p, [f]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    // Simulate API Call
    setTimeout(() => {
      toast.success('Institute settings updated successfully');
      setSaving(false);
    }, 800);
  };

  if (user?.role !== 'admin') {
    return <div className="p-12 text-center text-red-400">Access Denied. Admins only.</div>;
  }

  if (loading) return null;

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl pb-10">
      <div>
        <h1 className="page-title">Institute Settings</h1>
        <p className="page-subtitle">Manage global branding and contact information</p>
      </div>

      <form onSubmit={handleSubmit} className="card">
        <div className="card-header bg-slate-900 border-b border-slate-700">
          <h2 className="section-title">General Information</h2>
        </div>
        <div className="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
          
          <div className="form-group md:col-span-2">
            <label className="label">Institute Name *</label>
            <input className="input text-lg font-semibold" value={form.institute_name} onChange={set('institute_name')} required />
          </div>

          <div className="form-group">
            <label className="label">Email Address *</label>
            <input type="email" className="input" value={form.email} onChange={set('email')} required />
          </div>

          <div className="form-group">
            <label className="label">Phone Number *</label>
            <input className="input" value={form.phone} onChange={set('phone')} required />
          </div>

          <div className="form-group">
            <label className="label">Principal / Head Name</label>
            <input className="input" value={form.principal_name} onChange={set('principal_name')} />
          </div>

          <div className="form-group md:col-span-2">
            <label className="label">Complete Address</label>
            <textarea className="input h-20" value={form.address} onChange={set('address')} />
          </div>

          <div className="form-group md:col-span-2">
            <label className="label">Other Details / Description</label>
            <textarea className="input h-24" placeholder="Affiliation info, history, taglines..." value={form.other_details} onChange={set('other_details')} />
          </div>

        </div>
        <div className="card-body border-t border-slate-700/50 flex justify-end">
          <button type="submit" className="btn btn-primary px-8" disabled={saving}>
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default InstitutePage;