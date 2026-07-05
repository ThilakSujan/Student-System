import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getStaff, deleteStaff } from '../../api/staff';

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

const StatusBadge = ({ status }) => {
  const map = {
    Approved: 'badge-success',
    Pending: 'badge-warning',
    Rejected: 'badge-danger',
    Suspended: 'badge-slate',
  };
  return <span className={`badge ${map[status] || 'badge-slate'}`}>{status}</span>;
};

const Avatar = ({ name, username }) => {
  const text = name || username || '?';
  const initials = text.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
  const colors = [
    'from-indigo-500 to-indigo-700',
    'from-emerald-500 to-emerald-700',
    'from-amber-500 to-amber-700',
    'from-purple-500 to-purple-700',
  ];
  const color = colors[initials.charCodeAt(0) % colors.length];
  return (
    <div className={`w-9 h-9 rounded-full bg-gradient-to-br ${color} flex items-center justify-center text-white text-xs font-bold flex-shrink-0`}>
      {initials}
    </div>
  );
};

const DeleteModal = ({ member, onConfirm, onCancel }) => (
  <div className="modal-backdrop" onClick={onCancel}>
    <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
      <div className="card-header flex items-center justify-between">
        <h3 className="section-title text-red-400">Delete Staff Member</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete <span className="text-white font-semibold">{member.full_name || member.username}</span>? This action cannot be undone.
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const StaffPage = () => {
  const [staff, setStaff] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchStaff = useCallback(() => {
    setLoading(true);
    getStaff()
      .then(res => setStaff(res.data.data || []))
      .catch(() => toast.error('Failed to load staff members'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchStaff(); }, [fetchStaff]);

  const handleDelete = async () => {
    try {
      await deleteStaff(deleteTarget._id);
      toast.success(`${deleteTarget.full_name || deleteTarget.username} deleted successfully`);
      setDeleteTarget(null);
      fetchStaff();
    } catch {
      toast.error('Failed to delete staff member');
    }
  };

  const filtered = staff.filter(s => {
    const term = search.toLowerCase();
    return !search ||
      s.username?.toLowerCase().includes(term) ||
      s.email?.toLowerCase().includes(term) ||
      s.full_name?.toLowerCase().includes(term);
  });

  return (
    <div className="space-y-6 animate-fade-in">
      {/* ── Header ── */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Staff Members</h1>
          <p className="page-subtitle">Manage teaching and administrative staff — {staff.length} total</p>
        </div>
        <div className="flex gap-2">
          <Link to="/staff/add" className="btn btn-primary">
            <PlusIcon /> Add Staff
          </Link>
        </div>
      </div>

      {/* ── Filters ── */}
      <div className="card p-4 flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
          <input
            id="staff-search"
            type="text"
            className="input pl-9"
            placeholder="Search by name, username, or email…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        {search && (
          <button className="btn btn-ghost text-slate-400" onClick={() => setSearch('')}>
            <XMarkIcon /> Clear
          </button>
        )}
      </div>

      {/* ── Table ── */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Staff Member</th>
              <th>Username</th>
              <th>Phone</th>
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
                  {search ? 'No staff members match your search.' : 'No staff members found. Add your first staff member!'}
                </td>
              </tr>
            ) : (
              filtered.map(member => (
                <tr key={member._id}>
                  <td>
                    <div className="flex items-center gap-3">
                      <Avatar name={member.full_name} username={member.username} />
                      <div>
                        <p className="text-white font-medium text-sm">{member.full_name || '—'}</p>
                        <p className="text-slate-500 text-xs">{member.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="text-slate-300 text-sm">{member.username}</td>
                  <td className="text-slate-300 text-sm">{member.phone || '—'}</td>
                  <td><StatusBadge status={member.account_status} /></td>
                  <td>
                    <div className="flex items-center gap-1">
                      <Link to={`/staff/${member._id}/edit`} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
                        <EditIcon />
                      </Link>
                      <button
                        className="btn-icon btn-ghost text-slate-400 hover:text-red-400"
                        title="Delete"
                        onClick={() => setDeleteTarget(member)}
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

      {!loading && filtered.length > 0 && (
        <p className="text-xs text-slate-500 text-right">
          Showing {filtered.length} of {staff.length} staff member{staff.length !== 1 ? 's' : ''}
        </p>
      )}

      {deleteTarget && (
        <DeleteModal
          member={deleteTarget}
          onConfirm={handleDelete}
          onCancel={() => setDeleteTarget(null)}
        />
      )}
    </div>
  );
};

export default StaffPage;