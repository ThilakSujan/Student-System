import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getAllSubjects, createSubject, updateSubject, deleteSubject } from '../../api/subjects';

const PlusIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
  </svg>
);
const SearchIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
  </svg>
);
const EditIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
  </svg>
);
const TrashIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
  </svg>
);
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

const SubjectModal = ({ subject, onClose, onSave }) => {
  const [form, setForm] = useState({
    subject_code: subject?.subject_code || '',
    subject_name: subject?.subject_name || '',
    credit_hours: subject?.credit_hours || '',
    status: subject?.status || 'Active'
  });
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.subject_code.trim()) errs.subject_code = 'Code required';
    if (!form.subject_name.trim()) errs.subject_name = 'Name required';
    if (form.credit_hours === '' || isNaN(form.credit_hours) || form.credit_hours < 0) errs.credit_hours = 'Valid credit hours required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const data = { ...form, subject_code: form.subject_code.toUpperCase(), credit_hours: Number(form.credit_hours) };
      if (subject) await updateSubject(subject._id, data);
      else await createSubject(data);
      toast.success(`Subject ${subject ? 'updated' : 'added'} successfully`);
      onSave();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to save subject');
    } finally {
      setSaving(false);
    }
  };

  const set = (f) => (e) => {
    setForm(p => ({ ...p, [f]: e.target.value }));
    if (errors[f]) setErrors(p => ({ ...p, [f]: '' }));
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-box max-w-md" onClick={e => e.stopPropagation()}>
        <div className="card-header flex items-center justify-between">
          <h3 className="section-title">{subject ? 'Edit Subject' : 'Add Subject'}</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><XMarkIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body space-y-4">
          <div className="form-group">
            <label htmlFor="subject_code" className="label">Subject Code *</label>
            <input id="subject_code" className={`input uppercase ${errors.subject_code ? 'input-error' : ''}`} placeholder="e.g. MATH101" value={form.subject_code} onChange={set('subject_code')} />
            {errors.subject_code && <p className="error-msg">{errors.subject_code}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="subject_name" className="label">Subject Name *</label>
            <input id="subject_name" className={`input ${errors.subject_name ? 'input-error' : ''}`} placeholder="e.g. Mathematics" value={form.subject_name} onChange={set('subject_name')} />
            {errors.subject_name && <p className="error-msg">{errors.subject_name}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="credit_hours" className="label">Credit Hours *</label>
            <input id="credit_hours" type="number" min="0" className={`input ${errors.credit_hours ? 'input-error' : ''}`} placeholder="e.g. 3" value={form.credit_hours} onChange={set('credit_hours')} />
            {errors.credit_hours && <p className="error-msg">{errors.credit_hours}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="status" className="label">Status</label>
            <select id="status" className="input" value={form.status} onChange={set('status')}>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Save Subject'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const DeleteModal = ({ item, onConfirm, onCancel }) => (
  <div className="modal-backdrop" onClick={onCancel}>
    <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
      <div className="card-header flex items-center justify-between">
        <h3 className="section-title text-red-400">Delete Subject</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete <span className="text-white font-semibold">{item.subject_name} ({item.subject_code})</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const SubjectsPage = () => {
  const [subjects, setSubjects] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  
  const [modalSubject, setModalSubject] = useState(null); // null = closed, {} = add, {id...} = edit
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchSubjects = useCallback(() => {
    setLoading(true);
    getAllSubjects()
      .then(res => setSubjects(res.data.data || []))
      .catch(() => toast.error('Failed to load subjects'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchSubjects(); }, [fetchSubjects]);

  const handleDelete = async () => {
    try {
      await deleteSubject(deleteTarget._id);
      toast.success('Subject deleted successfully');
      setDeleteTarget(null);
      fetchSubjects();
    } catch {
      toast.error('Failed to delete subject');
    }
  };

  const filtered = subjects.filter(s => {
    const term = search.toLowerCase();
    return !search || 
      s.subject_name.toLowerCase().includes(term) || 
      s.subject_code.toLowerCase().includes(term);
  });

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Subjects</h1>
          <p className="page-subtitle">Manage curriculum subjects — {subjects.length} total</p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => setModalSubject({})} className="btn btn-primary">
            <PlusIcon /> Add Subject
          </button>
        </div>
      </div>

      <div className="card p-4">
        <div className="relative max-w-md">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
          <input
            type="text"
            className="input pl-9"
            placeholder="Search by code or name…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Subject Code</th>
              <th>Subject Name</th>
              <th>Credit Hours</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: 5 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={5} className="text-center py-12 text-slate-500">
                  {search ? 'No subjects found.' : 'No subjects added yet.'}
                </td>
              </tr>
            ) : (
              filtered.map(s => (
                <tr key={s._id}>
                  <td className="text-indigo-400 font-semibold text-sm">{s.subject_code}</td>
                  <td className="text-white font-medium text-sm">{s.subject_name}</td>
                  <td className="text-slate-300 text-sm">{s.credit_hours}</td>
                  <td>
                    <span className={`badge ${s.status === 'Active' ? 'badge-success' : 'badge-slate'}`}>
                      {s.status}
                    </span>
                  </td>
                  <td>
                    <div className="flex items-center gap-1">
                      <button onClick={() => setModalSubject(s)} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
                        <EditIcon />
                      </button>
                      <button onClick={() => setDeleteTarget(s)} className="btn-icon btn-ghost text-slate-400 hover:text-red-400" title="Delete">
                        <TrashIcon />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {modalSubject !== null && (
        <SubjectModal 
          subject={Object.keys(modalSubject).length ? modalSubject : null} 
          onClose={() => setModalSubject(null)}
          onSave={() => { setModalSubject(null); fetchSubjects(); }}
        />
      )}

      {deleteTarget && (
        <DeleteModal
          item={deleteTarget}
          onConfirm={handleDelete}
          onCancel={() => setDeleteTarget(null)}
        />
      )}
    </div>
  );
};

export default SubjectsPage;