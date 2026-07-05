import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAllClasses, deleteClass } from '../../api/classes';

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
const UsersIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
  </svg>
);
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

const DeleteModal = ({ item, onConfirm, onCancel }) => (
  <div className="modal-backdrop" onClick={onCancel}>
    <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
      <div className="card-header flex items-center justify-between">
        <h3 className="section-title text-red-400">Delete Class</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete <span className="text-white font-semibold">{item.class_name} {item.section ? `- ${item.section}` : ''}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const ClassesPage = () => {
  const [classesList, setClassesList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('All');
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchClasses = useCallback(() => {
    setLoading(true);
    getAllClasses()
      .then(res => setClassesList(res.data.data || []))
      .catch(() => toast.error('Failed to load classes'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchClasses(); }, [fetchClasses]);

  const handleDelete = async () => {
    try {
      await deleteClass(deleteTarget._id);
      toast.success('Class deleted successfully');
      setDeleteTarget(null);
      fetchClasses();
    } catch {
      toast.error('Failed to delete class');
    }
  };

  const filtered = classesList.filter(c => statusFilter === 'All' || c.status === statusFilter);

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Classes</h1>
          <p className="page-subtitle">Manage classes and sections — {classesList.length} total</p>
        </div>
        <div className="flex gap-2">
          <Link to="/classes/add" className="btn btn-primary">
            <PlusIcon /> Add Class
          </Link>
        </div>
      </div>

      <div className="card p-4 flex gap-3">
        <div className="form-group flex-1 max-w-xs mb-0">
          <select 
            className="input" 
            value={statusFilter} 
            onChange={e => setStatusFilter(e.target.value)}
          >
            <option value="All">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Class Name</th>
              <th>Section</th>
              <th>Academic Year</th>
              <th>Teacher</th>
              <th>Students</th>
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
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={7} className="text-center py-12 text-slate-500">
                  No classes found.
                </td>
              </tr>
            ) : (
              filtered.map(c => (
                <tr key={c._id}>
                  <td className="text-white font-medium text-sm">{c.class_name}</td>
                  <td className="text-slate-300 text-sm">{c.section || '—'}</td>
                  <td className="text-slate-300 text-sm">{c.academic_year}</td>
                  <td className="text-slate-300 text-sm">{c.class_teacher ? (c.class_teacher.full_name || c.class_teacher.username) : '—'}</td>
                  <td>
                    <span className="badge badge-info">{c.students?.length || 0}</span>
                  </td>
                  <td>
                    <span className={`badge ${c.status === 'Active' ? 'badge-success' : 'badge-slate'}`}>
                      {c.status}
                    </span>
                  </td>
                  <td>
                    <div className="flex items-center gap-1">
                      <Link to={`/classes/${c._id}/assign`} className="btn-icon btn-ghost text-slate-400 hover:text-indigo-400" title="Manage Students">
                        <UsersIcon />
                      </Link>
                      <Link to={`/classes/${c._id}/edit`} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
                        <EditIcon />
                      </Link>
                      <button
                        className="btn-icon btn-ghost text-slate-400 hover:text-red-400"
                        title="Delete"
                        onClick={() => setDeleteTarget(c)}
                      >
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

export default ClassesPage;