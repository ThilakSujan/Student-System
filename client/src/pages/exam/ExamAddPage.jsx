import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { createExam } from '../../api/exams';
import { getAllClasses } from '../../api/classes';
import { getAllSubjects } from '../../api/subjects';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const ExamAddPage = () => {
  const navigate = useNavigate();
  const [saving, setSaving] = useState(false);
  const [classes, setClasses] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [errors, setErrors] = useState({});
  const [form, setForm] = useState({
    exam_title: '',
    subject: '',
    class: '',
    exam_date: '',
    start_time: '',
    end_time: '',
    venue: '',
    exam_type: 'Internal',
    description: '',
    status: 'Scheduled'
  });

  useEffect(() => {
    Promise.all([getAllClasses(), getAllSubjects()])
      .then(([classRes, subRes]) => {
        setClasses((classRes.data.data || []).filter(c => c.status === 'Active'));
        setSubjects((subRes.data.data || []).filter(s => s.status === 'Active'));
      })
      .catch(() => toast.error('Failed to load form data'));
  }, []);

  const set = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: '' }));
  };

  const validate = () => {
    const errs = {};
    if (!form.exam_title.trim()) errs.exam_title = 'Title required';
    if (!form.subject) errs.subject = 'Subject required';
    if (!form.class) errs.class = 'Class required';
    if (!form.exam_date) errs.exam_date = 'Date required';
    if (!form.start_time) errs.start_time = 'Start time required';
    if (!form.end_time) errs.end_time = 'End time required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      await createExam(form);
      toast.success('Exam scheduled successfully!');
      navigate('/exams');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to schedule exam');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl pb-10">
      <div className="flex items-center gap-4">
        <Link to="/exams" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Schedule Exam</h1>
          <p className="page-subtitle">Add a new examination to the schedule</p>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="card">
        <div className="card-body grid grid-cols-1 md:grid-cols-2 gap-5">
          <div className="form-group md:col-span-2">
            <label htmlFor="title" className="label">Exam Title *</label>
            <input id="title" className={`input ${errors.exam_title ? 'input-error' : ''}`} placeholder="e.g. Mid-Term Examination 2024" value={form.exam_title} onChange={set('exam_title')} />
            {errors.exam_title && <p className="error-msg">{errors.exam_title}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="subject" className="label">Subject *</label>
            <select id="subject" className={`input ${errors.subject ? 'input-error' : ''}`} value={form.subject} onChange={set('subject')}>
              <option value="">-- Select Subject --</option>
              {subjects.map(s => <option key={s._id} value={s._id}>{s.subject_name}</option>)}
            </select>
            {errors.subject && <p className="error-msg">{errors.subject}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="class" className="label">Class *</label>
            <select id="class" className={`input ${errors.class ? 'input-error' : ''}`} value={form.class} onChange={set('class')}>
              <option value="">-- Select Class --</option>
              {classes.map(c => <option key={c._id} value={c._id}>{c.class_name} {c.section ? `(${c.section})` : ''}</option>)}
            </select>
            {errors.class && <p className="error-msg">{errors.class}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="date" className="label">Exam Date *</label>
            <input id="date" type="date" className={`input ${errors.exam_date ? 'input-error' : ''}`} value={form.exam_date} onChange={set('exam_date')} />
            {errors.exam_date && <p className="error-msg">{errors.exam_date}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="venue" className="label">Venue / Room</label>
            <input id="venue" className="input" placeholder="e.g. Hall A" value={form.venue} onChange={set('venue')} />
          </div>

          <div className="form-group">
            <label htmlFor="start" className="label">Start Time *</label>
            <input id="start" type="time" className={`input ${errors.start_time ? 'input-error' : ''}`} value={form.start_time} onChange={set('start_time')} />
            {errors.start_time && <p className="error-msg">{errors.start_time}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="end" className="label">End Time *</label>
            <input id="end" type="time" className={`input ${errors.end_time ? 'input-error' : ''}`} value={form.end_time} onChange={set('end_time')} />
            {errors.end_time && <p className="error-msg">{errors.end_time}</p>}
          </div>

          <div className="form-group">
            <label htmlFor="type" className="label">Exam Type</label>
            <select id="type" className="input" value={form.exam_type} onChange={set('exam_type')}>
              <option value="Internal">Internal</option>
              <option value="External">External</option>
              <option value="Practical">Practical</option>
              <option value="Viva">Viva</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div className="form-group">
            <label htmlFor="status" className="label">Status</label>
            <select id="status" className="input" value={form.status} onChange={set('status')}>
              <option value="Scheduled">Scheduled</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>

          <div className="form-group md:col-span-2">
            <label htmlFor="desc" className="label">Description / Instructions</label>
            <textarea id="desc" className="input h-24" placeholder="Any special instructions for students..." value={form.description} onChange={set('description')} />
          </div>
        </div>
        
        <div className="card-body border-t border-slate-700/50 flex justify-end gap-3">
          <Link to="/exams" className="btn btn-secondary">Cancel</Link>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Scheduling...' : 'Schedule Exam'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ExamAddPage;