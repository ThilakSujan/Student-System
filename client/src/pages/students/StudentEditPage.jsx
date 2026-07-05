import { useEffect, useState } from 'react';
import { useNavigate, useParams, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getStudent, updateStudent } from '../../api/students';

const DEPARTMENTS = ['Computer Science', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Commerce', 'Arts', 'Engineering', 'Other'];

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const StudentEditPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [loadingStudent, setLoadingStudent] = useState(true);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    student_name: '', email: '', phone: '', gender: '',
    dob: '', department: '', parent_name: '', parent_email: '',
    status: 'Active', skills: '',
  });

  useEffect(() => {
    getStudent(id)
      .then(res => {
        const s = res.data.data;
        setForm({
          student_name: s.student_name || '',
          email: s.email || '',
          phone: s.phone || '',
          gender: s.gender || '',
          dob: s.dob ? new Date(s.dob).toISOString().split('T')[0] : '',
          department: s.department || '',
          parent_name: s.parent_name || '',
          parent_email: s.parent_email || '',
          status: s.status || 'Active',
          skills: Array.isArray(s.skills) ? s.skills.join(', ') : (s.skills || ''),
        });
      })
      .catch(() => toast.error('Failed to load student'))
      .finally(() => setLoadingStudent(false));
  }, [id]);

  const set = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const errs = {};
    if (!form.student_name.trim()) errs.student_name = 'Name is required';
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
        ...form,
        skills: form.skills ? form.skills.split(',').map(s => s.trim()).filter(Boolean) : [],
        dob: form.dob || undefined,
        phone: form.phone || undefined,
        parent_email: form.parent_email || undefined,
      };
      await updateStudent(id, payload);
      toast.success('Student updated successfully!');
      navigate(`/students/${id}`);
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to update student');
    } finally {
      setSaving(false);
    }
  };

  if (loadingStudent) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-3xl">
      <div className="flex items-center gap-4">
        <Link to={`/students/${id}`} className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Edit Student</h1>
          <p className="page-subtitle">Update student information</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="card">
          <div className="card-header"><h2 className="section-title">Personal Information</h2></div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="student_name" className="label">Full Name *</label>
              <input id="student_name" className={`input ${errors.student_name ? 'input-error' : ''}`} value={form.student_name} onChange={set('student_name')} />
              {errors.student_name && <p className="error-msg">{errors.student_name}</p>}
            </div>
            <div className="form-group">
              <label htmlFor="email" className="label">Email *</label>
              <input id="email" type="email" className={`input ${errors.email ? 'input-error' : ''}`} value={form.email} onChange={set('email')} />
              {errors.email && <p className="error-msg">{errors.email}</p>}
            </div>
            <div className="form-group">
              <label htmlFor="phone" className="label">Phone</label>
              <input id="phone" className="input" value={form.phone} onChange={set('phone')} />
            </div>
            <div className="form-group">
              <label htmlFor="gender" className="label">Gender</label>
              <select id="gender" className="input" value={form.gender} onChange={set('gender')}>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div className="form-group">
              <label htmlFor="dob" className="label">Date of Birth</label>
              <input id="dob" type="date" className="input" value={form.dob} onChange={set('dob')} />
            </div>
            <div className="form-group">
              <label htmlFor="status" className="label">Status</label>
              <select id="status" className="input" value={form.status} onChange={set('status')}>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Graduated">Graduated</option>
                <option value="Expelled">Expelled</option>
              </select>
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header"><h2 className="section-title">Academic Information</h2></div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group sm:col-span-2">
              <label htmlFor="department" className="label">Department</label>
              <select id="department" className="input" value={form.department} onChange={set('department')}>
                <option value="">Select department</option>
                {DEPARTMENTS.map(d => <option key={d} value={d}>{d}</option>)}
              </select>
            </div>
            <div className="form-group sm:col-span-2">
              <label htmlFor="skills" className="label">Skills / Interests</label>
              <input id="skills" className="input" placeholder="Comma-separated" value={form.skills} onChange={set('skills')} />
            </div>
          </div>
        </div>

        <div className="card">
          <div className="card-header"><h2 className="section-title">Parent / Guardian</h2></div>
          <div className="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div className="form-group">
              <label htmlFor="parent_name" className="label">Parent Name</label>
              <input id="parent_name" className="input" value={form.parent_name} onChange={set('parent_name')} />
            </div>
            <div className="form-group">
              <label htmlFor="parent_email" className="label">Parent Email</label>
              <input id="parent_email" type="email" className="input" value={form.parent_email} onChange={set('parent_email')} />
            </div>
          </div>
        </div>

        <div className="flex gap-3 justify-end">
          <Link to={`/students/${id}`} className="btn btn-secondary">Cancel</Link>
          <button id="update-student" type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? <><span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> Saving…</> : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default StudentEditPage;
