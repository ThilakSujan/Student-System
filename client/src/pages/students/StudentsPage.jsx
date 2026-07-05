import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAllStudents, deleteStudent } from '../../api/students';

// ─── Icons ────────────────────────────────────────────────────────────────────
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
const EyeIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.641 0-8.573-3.007-9.963-7.178Z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
  </svg>
);
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

// ─── Status Badge ─────────────────────────────────────────────────────────────
const StatusBadge = ({ status }) => {
  const map = {
    Active: 'badge-success',
    Inactive: 'badge-slate',
    Graduated: 'badge-info',
    Expelled: 'badge-danger',
  };
  return <span className={`badge ${map[status] || 'badge-slate'}`}>{status}</span>;
};

// ─── Avatar ───────────────────────────────────────────────────────────────────
const Avatar = ({ name }) => {
  const initials = name?.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() || '?';
  const colors = [
    'from-indigo-500 to-indigo-700',
    'from-emerald-500 to-emerald-700',
    'from-amber-500 to-amber-700',
    'from-pink-500 to-rose-700',
    'from-purple-500 to-purple-700',
  ];
  const color = colors[initials.charCodeAt(0) % colors.length];
  return (
    <div className={`w-9 h-9 rounded-full bg-gradient-to-br ${color} flex items-center justify-center text-white text-xs font-bold flex-shrink-0`}>
      {initials}
    </div>
  );
};

// ─── Delete Confirm Modal ─────────────────────────────────────────────────────
const DeleteModal = ({ student, onConfirm, onCancel }) => (
  <div className="modal-backdrop" onClick={onCancel}>
    <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
      <div className="card-header flex items-center justify-between">
        <h3 className="section-title text-red-400">Delete Student</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete <span className="text-white font-semibold">{student.student_name}</span>? This action cannot be undone.
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

// ─── Main Students Page ───────────────────────────────────────────────────────
const StudentsPage = () => {
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchStudents = useCallback(() => {
    setLoading(true);
    getAllStudents()
      .then(res => setStudents(res.data.data || []))
      .catch(() => toast.error('Failed to load students'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchStudents(); }, [fetchStudents]);

  const handleDelete = async () => {
    try {
      await deleteStudent(deleteTarget._id);
      toast.success(`${deleteTarget.student_name} deleted successfully`);
      setDeleteTarget(null);
      fetchStudents();
    } catch {
      toast.error('Failed to delete student');
    }
  };

  const filtered = students.filter(s => {
    const matchSearch = !search ||
      s.student_name?.toLowerCase().includes(search.toLowerCase()) ||
      s.email?.toLowerCase().includes(search.toLowerCase()) ||
      s.department?.toLowerCase().includes(search.toLowerCase());
    const matchStatus = !statusFilter || s.status === statusFilter;
    return matchSearch && matchStatus;
  });

  return (
    <div className="space-y-6 animate-fade-in">
      {/* ── Header ── */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Students</h1>
          <p className="page-subtitle">Manage enrolled students — {students.length} total</p>
        </div>
        <div className="flex gap-2">
          <Link to="/students/add" className="btn btn-primary">
            <PlusIcon /> Add Student
          </Link>
        </div>
      </div>

      {/* ── Filters ── */}
      <div className="card p-4 flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
          <input
            id="students-search"
            type="text"
            className="input pl-9"
            placeholder="Search by name, email, or department…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
        <select
          id="students-status-filter"
          className="input sm:w-44"
          value={statusFilter}
          onChange={e => setStatusFilter(e.target.value)}
        >
          <option value="">All Statuses</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Graduated">Graduated</option>
          <option value="Expelled">Expelled</option>
        </select>
        {(search || statusFilter) && (
          <button
            className="btn btn-ghost text-slate-400"
            onClick={() => { setSearch(''); setStatusFilter(''); }}
          >
            <XMarkIcon /> Clear
          </button>
        )}
      </div>

      {/* ── Table ── */}
      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Department</th>
              <th>Gender</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: 7 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={7} className="text-center py-12 text-slate-500">
                  {search || statusFilter ? 'No students match your search.' : 'No students found. Add your first student!'}
                </td>
              </tr>
            ) : (
              filtered.map(student => (
                <tr key={student._id}>
                  <td>
                    <div className="flex items-center gap-3">
                      <Avatar name={student.student_name} />
                      <div>
                        <p className="text-white font-medium text-sm">{student.student_name}</p>
                        <p className="text-slate-500 text-xs">{student.email}</p>
                      </div>
                    </div>
                  </td>
                  <td className="text-slate-300 text-sm">{student.department || '—'}</td>
                  <td className="text-slate-300 text-sm">{student.gender || '—'}</td>
                  <td className="text-slate-300 text-sm">{student.phone || '—'}</td>
                  <td><StatusBadge status={student.status} /></td>
                  <td className="text-slate-400 text-sm">
                    {student.createdAt ? new Date(student.createdAt).toLocaleDateString() : '—'}
                  </td>
                  <td>
                    <div className="flex items-center gap-1">
                      <Link to={`/students/${student._id}`} className="btn-icon btn-ghost text-slate-400 hover:text-indigo-400" title="View">
                        <EyeIcon />
                      </Link>
                      <Link to={`/students/${student._id}/edit`} className="btn-icon btn-ghost text-slate-400 hover:text-amber-400" title="Edit">
                        <EditIcon />
                      </Link>
                      <button
                        className="btn-icon btn-ghost text-slate-400 hover:text-red-400"
                        title="Delete"
                        onClick={() => setDeleteTarget(student)}
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

      {/* ── Summary footer ── */}
      {!loading && filtered.length > 0 && (
        <p className="text-xs text-slate-500 text-right">
          Showing {filtered.length} of {students.length} student{students.length !== 1 ? 's' : ''}
        </p>
      )}

      {/* ── Delete Modal ── */}
      {deleteTarget && (
        <DeleteModal
          student={deleteTarget}
          onConfirm={handleDelete}
          onCancel={() => setDeleteTarget(null)}
        />
      )}
    </div>
  );
};

export default StudentsPage;
