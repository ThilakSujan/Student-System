import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAllMarks, deleteMark, publishMarks } from '../../api/marks';
import { useAuth } from '../../context/AuthContext';

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
const TrashIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
  </svg>
);
const ShareIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
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
        <h3 className="section-title text-red-400">Delete Mark</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete this mark record for <span className="text-white font-semibold">{item.student?.student_name}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const MarksPage = () => {
  const { user } = useAuth();
  const isAdminOrStaff = user?.role === 'admin' || user?.role === 'staff';
  
  const [marks, setMarks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('All');
  
  const [selectedIds, setSelectedIds] = useState(new Set());
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [publishing, setPublishing] = useState(false);

  const fetchMarks = useCallback(() => {
    setLoading(true);
    getAllMarks()
      .then(res => {
        setMarks(res.data.data || []);
        setSelectedIds(new Set());
      })
      .catch(() => toast.error('Failed to load marks'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchMarks(); }, [fetchMarks]);

  const handleDelete = async () => {
    try {
      await deleteMark(deleteTarget._id);
      toast.success('Mark deleted successfully');
      setDeleteTarget(null);
      fetchMarks();
    } catch {
      toast.error('Failed to delete mark');
    }
  };

  const handlePublish = async () => {
    if (selectedIds.size === 0) return;
    setPublishing(true);
    try {
      await publishMarks(Array.from(selectedIds));
      toast.success(`${selectedIds.size} records published!`);
      fetchMarks();
    } catch {
      toast.error('Failed to publish marks');
      setPublishing(false);
    }
  };

  const toggleSelect = (id) => {
    const next = new Set(selectedIds);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    setSelectedIds(next);
  };

  const toggleSelectAll = (filteredItems) => {
    if (selectedIds.size === filteredItems.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(filteredItems.map(m => m._id)));
    }
  };

  const filtered = marks.filter(m => {
    const term = search.toLowerCase();
    const nameMatch = !search || m.student?.student_name?.toLowerCase().includes(term) || m.subject?.subject_name?.toLowerCase().includes(term);
    const statusMatch = statusFilter === 'All' || 
                        (statusFilter === 'Published' && m.published) || 
                        (statusFilter === 'Draft' && !m.published);
    return nameMatch && statusMatch;
  });

  const getGradeBadge = (grade) => {
    const map = { 'A+': 'badge-success', 'A': 'badge-success', 'B': 'badge-info', 'C': 'badge-warning', 'D': 'badge-warning', 'F': 'badge-danger' };
    return <span className={`badge ${map[grade] || 'badge-slate'}`}>{grade}</span>;
  };

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Marks & Grades</h1>
          <p className="page-subtitle">Manage student academic performance</p>
        </div>
        {isAdminOrStaff && (
          <div className="flex gap-2">
            <Link to="/marks/add" className="btn btn-primary">
              <PlusIcon /> Enter Marks
            </Link>
          </div>
        )}
      </div>

      <div className="card p-4 flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1 max-w-md">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
          <input
            type="text"
            className="input pl-9"
            placeholder="Search by student or subject…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        {isAdminOrStaff && (
          <div className="form-group flex-1 max-w-xs mb-0">
            <select className="input" value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
              <option value="All">All Statuses</option>
              <option value="Published">Published</option>
              <option value="Draft">Draft</option>
            </select>
          </div>
        )}
        
        {isAdminOrStaff && selectedIds.size > 0 && (
          <div className="ml-auto">
            <button onClick={handlePublish} className="btn btn-primary bg-emerald-600 hover:bg-emerald-500" disabled={publishing}>
              <ShareIcon /> {publishing ? 'Publishing...' : `Publish Selected (${selectedIds.size})`}
            </button>
          </div>
        )}
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              {isAdminOrStaff && (
                <th className="w-12">
                  <input 
                    type="checkbox" 
                    className="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
                    checked={filtered.length > 0 && selectedIds.size === filtered.length}
                    onChange={() => toggleSelectAll(filtered)}
                  />
                </th>
              )}
              <th>Student</th>
              <th>Subject</th>
              <th>Marks</th>
              <th>%</th>
              <th>Grade</th>
              <th>Status</th>
              {isAdminOrStaff && <th>Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: isAdminOrStaff ? 8 : 6 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={isAdminOrStaff ? 8 : 6} className="text-center py-12 text-slate-500">
                  {search ? 'No marks match your search.' : 'No marks recorded yet.'}
                </td>
              </tr>
            ) : (
              filtered.map(m => (
                <tr key={m._id}>
                  {isAdminOrStaff && (
                    <td>
                      <input 
                        type="checkbox" 
                        className="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500"
                        checked={selectedIds.has(m._id)}
                        onChange={() => toggleSelect(m._id)}
                        disabled={m.published}
                      />
                    </td>
                  )}
                  <td className="text-white font-medium text-sm">{m.student?.student_name || '—'}</td>
                  <td>
                    <p className="text-indigo-400 text-sm">{m.subject?.subject_code}</p>
                    <p className="text-slate-400 text-xs">{m.subject?.subject_name}</p>
                  </td>
                  <td className="text-slate-300 font-mono text-sm">{m.marks_obtained} / {m.total_marks}</td>
                  <td className="text-slate-300 font-mono text-sm">{m.percentage}%</td>
                  <td>{getGradeBadge(m.grade)}</td>
                  <td>
                    {m.published ? 
                      <span className="badge badge-success text-xs">Published</span> : 
                      <span className="badge badge-slate text-xs">Draft</span>
                    }
                  </td>
                  {isAdminOrStaff && (
                    <td>
                      <button
                        className="btn-icon btn-ghost text-slate-400 hover:text-red-400"
                        title="Delete"
                        onClick={() => setDeleteTarget(m)}
                        disabled={m.published}
                      >
                        <TrashIcon />
                      </button>
                    </td>
                  )}
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

export default MarksPage;