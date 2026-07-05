import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getFeeStructures, createFeeStructure, updateFeeStructure, deleteFeeStructure, getFeeCategories } from '../../api/fees';
import { getAllClasses } from '../../api/classes';

const PlusIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
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

const StructureModal = ({ item, categories, classes, onClose, onSave }) => {
  const [form, setForm] = useState({
    category: item?.category?._id || '',
    class: item?.class?._id || '',
    academic_year: item?.academic_year || '2024-2025',
    amount: item?.amount || '',
    due_date: item?.due_date ? new Date(item.due_date).toISOString().split('T')[0] : '',
    description: item?.description || '',
    status: item?.status || 'Active'
  });
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.category) errs.category = 'Category required';
    if (!form.class) errs.class = 'Class required';
    if (!form.academic_year) errs.academic_year = 'Year required';
    if (!form.amount || isNaN(form.amount) || form.amount <= 0) errs.amount = 'Valid amount required';
    if (!form.due_date) errs.due_date = 'Due date required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const payload = { ...form, amount: Number(form.amount) };
      if (item) await updateFeeStructure(item._id, payload);
      else await createFeeStructure(payload);
      toast.success(`Structure ${item ? 'updated' : 'added'} successfully`);
      onSave();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to save structure');
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
      <div className="modal-box max-w-lg" onClick={e => e.stopPropagation()}>
        <div className="card-header flex items-center justify-between">
          <h3 className="section-title">{item ? 'Edit Fee Structure' : 'Add Fee Structure'}</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><XMarkIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="form-group">
            <label htmlFor="cat" className="label">Category *</label>
            <select id="cat" className={`input ${errors.category ? 'input-error' : ''}`} value={form.category} onChange={set('category')}>
              <option value="">-- Select --</option>
              {categories.map(c => <option key={c._id} value={c._id}>{c.name}</option>)}
            </select>
            {errors.category && <p className="error-msg">{errors.category}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="cls" className="label">Class *</label>
            <select id="cls" className={`input ${errors.class ? 'input-error' : ''}`} value={form.class} onChange={set('class')}>
              <option value="">-- Select --</option>
              {classes.map(c => <option key={c._id} value={c._id}>{c.class_name} {c.section ? `(${c.section})` : ''}</option>)}
            </select>
            {errors.class && <p className="error-msg">{errors.class}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="amt" className="label">Amount (₹) *</label>
            <input id="amt" type="number" min="0" className={`input ${errors.amount ? 'input-error' : ''}`} value={form.amount} onChange={set('amount')} />
            {errors.amount && <p className="error-msg">{errors.amount}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="due" className="label">Due Date *</label>
            <input id="due" type="date" className={`input ${errors.due_date ? 'input-error' : ''}`} value={form.due_date} onChange={set('due_date')} />
            {errors.due_date && <p className="error-msg">{errors.due_date}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="yr" className="label">Academic Year *</label>
            <input id="yr" className={`input ${errors.academic_year ? 'input-error' : ''}`} value={form.academic_year} onChange={set('academic_year')} />
            {errors.academic_year && <p className="error-msg">{errors.academic_year}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="status" className="label">Status</label>
            <select id="status" className="input" value={form.status} onChange={set('status')}>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div className="form-group sm:col-span-2">
            <label htmlFor="desc" className="label">Description</label>
            <input id="desc" className="input" value={form.description} onChange={set('description')} />
          </div>
          
          <div className="flex justify-end gap-3 pt-2 sm:col-span-2 border-t border-slate-700/50 mt-2">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Save Structure'}
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
        <h3 className="section-title text-red-400">Delete Structure</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete this structure for <span className="text-white font-semibold">{item.category?.name} - {item.class?.class_name}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const FeeStructuresPage = () => {
  const [structures, setStructures] = useState([]);
  const [categories, setCategories] = useState([]);
  const [classes, setClasses] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const [modalItem, setModalItem] = useState(null); // null = closed, {} = add, {...} = edit
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchData = useCallback(() => {
    setLoading(true);
    Promise.all([getFeeStructures(), getFeeCategories(), getAllClasses()])
      .then(([structRes, catRes, classRes]) => {
        setStructures(structRes.data.data || []);
        setCategories((catRes.data.data || []).filter(c => c.status === 'Active'));
        setClasses((classRes.data.data || []).filter(c => c.status === 'Active'));
      })
      .catch(() => toast.error('Failed to load data'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleDelete = async () => {
    try {
      await deleteFeeStructure(deleteTarget._id);
      toast.success('Structure deleted successfully');
      setDeleteTarget(null);
      fetchData();
    } catch {
      toast.error('Failed to delete structure (make sure no payments depend on it)');
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Fee Structures</h1>
          <p className="page-subtitle">Assign fee amounts to classes per academic year</p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => setModalItem({})} className="btn btn-primary">
            <PlusIcon /> Add Structure
          </button>
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Class</th>
              <th>Year</th>
              <th>Amount (₹)</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: 7 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : structures.length === 0 ? (
              <tr>
                <td colSpan={7} className="text-center py-12 text-slate-500">
                  No fee structures found.
                </td>
              </tr>
            ) : (
              structures.map(s => (
                <tr key={s._id}>
                  <td className="text-indigo-400 font-semibold text-sm">{s.category?.name || '—'}</td>
                  <td className="text-white text-sm">{s.class?.class_name} {s.class?.section ? `(${s.class?.section})` : ''}</td>
                  <td className="text-slate-300 text-sm">{s.academic_year}</td>
                  <td className="text-emerald-400 font-mono text-sm">₹ {s.amount.toLocaleString()}</td>
                  <td className="text-slate-300 text-sm">{new Date(s.due_date).toLocaleDateString()}</td>
                  <td>
                    <span className={`badge ${s.status === 'Active' ? 'badge-success' : 'badge-slate'}`}>
                      {s.status}
                    </span>
                  </td>
                  <td>
                    <div className="flex items-center gap-1">
                      <button onClick={() => setModalItem(s)} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
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

      {modalItem !== null && (
        <StructureModal 
          item={Object.keys(modalItem).length ? modalItem : null} 
          categories={categories}
          classes={classes}
          onClose={() => setModalItem(null)}
          onSave={() => { setModalItem(null); fetchData(); }}
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

export default FeeStructuresPage;