import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getFeeCategories, createFeeCategory, updateFeeCategory, deleteFeeCategory } from '../../api/fees';

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

const CategoryModal = ({ item, onClose, onSave }) => {
  const [form, setForm] = useState({
    name: item?.name || '',
    description: item?.description || '',
    is_permanent: item?.is_permanent || false,
    status: item?.status || 'Active'
  });
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.name.trim()) { setError('Name is required'); return; }

    setSaving(true);
    try {
      if (item) await updateFeeCategory(item._id, form);
      else await createFeeCategory(form);
      toast.success(`Category ${item ? 'updated' : 'added'} successfully`);
      onSave();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to save category');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-box max-w-md" onClick={e => e.stopPropagation()}>
        <div className="card-header flex items-center justify-between">
          <h3 className="section-title">{item ? 'Edit Category' : 'Add Category'}</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><XMarkIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body space-y-4">
          <div className="form-group">
            <label htmlFor="name" className="label">Category Name *</label>
            <input 
              id="name" 
              className={`input ${error ? 'input-error' : ''}`} 
              placeholder="e.g. Tuition Fee" 
              value={form.name} 
              onChange={e => { setForm({ ...form, name: e.target.value }); setError(''); }} 
            />
            {error && <p className="error-msg">{error}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="desc" className="label">Description</label>
            <textarea id="desc" className="input h-20" value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} />
          </div>
          <div className="flex items-center gap-3 py-2">
            <input 
              id="perm" 
              type="checkbox" 
              className="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
              checked={form.is_permanent}
              onChange={e => setForm({ ...form, is_permanent: e.target.checked })} 
            />
            <label htmlFor="perm" className="text-sm text-slate-300">Is Permanent (applied every year)</label>
          </div>
          <div className="form-group">
            <label htmlFor="status" className="label">Status</label>
            <select id="status" className="input" value={form.status} onChange={e => setForm({ ...form, status: e.target.value })}>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Save Category'}
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
        <h3 className="section-title text-red-400">Delete Category</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete <span className="text-white font-semibold">{item.name}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const FeeCategoriesPage = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [modalItem, setModalItem] = useState(null); // null = closed, {} = add, {...} = edit
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchCategories = useCallback(() => {
    setLoading(true);
    getFeeCategories()
      .then(res => setCategories(res.data.data || []))
      .catch(() => toast.error('Failed to load categories'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchCategories(); }, [fetchCategories]);

  const handleDelete = async () => {
    try {
      await deleteFeeCategory(deleteTarget._id);
      toast.success('Category deleted successfully');
      setDeleteTarget(null);
      fetchCategories();
    } catch {
      toast.error('Failed to delete category (make sure no fee structures depend on it)');
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Fee Categories</h1>
          <p className="page-subtitle">Manage types of fees (Tuition, Transport, etc.)</p>
        </div>
        <div className="flex gap-2">
          <button onClick={() => setModalItem({})} className="btn btn-primary">
            <PlusIcon /> Add Category
          </button>
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Category Name</th>
              <th>Description</th>
              <th>Permanent</th>
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
            ) : categories.length === 0 ? (
              <tr>
                <td colSpan={5} className="text-center py-12 text-slate-500">
                  No fee categories found.
                </td>
              </tr>
            ) : (
              categories.map(c => (
                <tr key={c._id}>
                  <td className="text-white font-medium text-sm">{c.name}</td>
                  <td className="text-slate-400 text-sm">{c.description || '—'}</td>
                  <td>
                    {c.is_permanent ? <span className="badge badge-info text-xs">Yes</span> : <span className="badge badge-slate text-xs">No</span>}
                  </td>
                  <td>
                    <span className={`badge ${c.status === 'Active' ? 'badge-success' : 'badge-slate'}`}>
                      {c.status}
                    </span>
                  </td>
                  <td>
                    <div className="flex items-center gap-1">
                      <button onClick={() => setModalItem(c)} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
                        <EditIcon />
                      </button>
                      <button onClick={() => setDeleteTarget(c)} className="btn-icon btn-ghost text-slate-400 hover:text-red-400" title="Delete">
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
        <CategoryModal 
          item={Object.keys(modalItem).length ? modalItem : null} 
          onClose={() => setModalItem(null)}
          onSave={() => { setModalItem(null); fetchCategories(); }}
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

export default FeeCategoriesPage;