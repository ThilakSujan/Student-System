import { useState, useEffect } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getClass, updateClass } from '../../api/classes';
import { getStaff } from '../../api/staff';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const ClassEditPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);
  const [staffList, setStaffList] = useState([]);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    class_name: '',
    section: '',
    academic_year: '',
    class_teacher: '',
    description: '',
    status: 'Active'
  });

  useEffect(() => {
    Promise.all([getClass(id), getStaff()])
      .then(([classRes, staffRes]) => {
        setStaffList(staffRes.data.data.filter(s => s.account_status === 'Approved') || []);
        const c = classRes.data.data;
        setForm({
          class_name: c.class_name || '',
          section: c.section || '',
          academic_year: c.academic_year || '',
          class_teacher: c.class_teacher?._id || '',
          description: c.description || '',
          status: c.status || 'Active'
        });
      })
      .catch(() => toast.error('Failed to load data'))
      .finally(() => setLoading(false));
  }, [id]);

  const set = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const errs = {};
    if (!form.class_name.trim()) errs.class_name = 'Class name is required';
    if (!form.academic_year.trim()) errs.academic_year = 'Academic year is required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const payload = { ...form };
      if (!payload.class_teacher) payload.class_teacher = null;
      await updateClass(id, payload);
      toast.success('Class updated successfully!');
      navigate('/classes');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to update class');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <div className="flex items-center gap-4">
        <Link to="/classes" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Edit Class</h1>
          <p className="page-subtitle">Update class information</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="card">
        <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div className="form-group">
            <label htmlFor="class_name" className="label">Class Name *</label>
            <input id="class_name" className={`input ${errors.class_name ? 'input-error' : ''}`} value={form.class_name} onChange={set('class_name')} />
            {errors.class_name && <p className="error-msg">{errors.class_name}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="section" className="label">Section</label>
            <input id="section" className="input" value={form.section} onChange={set('section')} />
          </div>

          <div className="form-group">
            <label htmlFor="academic_year" className="label">Academic Year *</label>
            <input id="academic_year" className={`input ${errors.academic_year ? 'input-error' : ''}`} value={form.academic_year} onChange={set('academic_year')} />
            {errors.academic_year && <p className="error-msg">{errors.academic_year}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="class_teacher" className="label">Class Teacher</label>
            <select id="class_teacher" className="input" value={form.class_teacher} onChange={set('class_teacher')}>
              <option value="">-- None --</option>
              {staffList.map(s => (
                <option key={s._id} value={s._id}>{s.full_name || s.username}</option>
              ))}
            </select>
          </div>

          <div className="form-group sm:col-span-2">
            <label htmlFor="description" className="label">Description</label>
            <textarea id="description" className="input h-24" value={form.description} onChange={set('description')} />
          </div>

          <div className="form-group">
            <label htmlFor="status" className="label">Status</label>
            <select id="status" className="input" value={form.status} onChange={set('status')}>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        
        <div className="card-body border-t border-slate-700/50 flex justify-end gap-3">
          <Link to="/classes" className="btn btn-secondary">Cancel</Link>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Saving...' : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ClassEditPage;