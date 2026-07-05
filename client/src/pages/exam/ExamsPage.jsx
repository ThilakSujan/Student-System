import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getExams, deleteExam, updateExam } from '../../api/exams';
import { useAuth } from '../../context/AuthContext';

const PlusIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
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

const DeleteModal = ({ item, onConfirm, onCancel }) => (
  <div className="modal-backdrop" onClick={onCancel}>
    <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
      <div className="card-header flex items-center justify-between">
        <h3 className="section-title text-red-400">Delete Exam</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete exam <span className="text-white font-semibold">{item.exam_title}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const ExamsPage = () => {
  const { user } = useAuth();
  const isStaff = user?.role === 'admin' || user?.role === 'staff';
  
  const [exams, setExams] = useState([]);
  const [loading, setLoading] = useState(true);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchExams = useCallback(() => {
    setLoading(true);
    getExams()
      .then(res => setExams(res.data.data || []))
      .catch(() => toast.error('Failed to load exams'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchExams(); }, [fetchExams]);

  const handleDelete = async () => {
    try {
      await deleteExam(deleteTarget._id);
      toast.success('Exam deleted successfully');
      setDeleteTarget(null);
      fetchExams();
    } catch {
      toast.error('Failed to delete exam');
    }
  };

  const handleStatusChange = async (id, status) => {
    try {
      await updateExam(id, { status });
      toast.success('Status updated');
      fetchExams();
    } catch {
      toast.error('Failed to update status');
    }
  };

  const getTypeBadge = (type) => {
    const map = {
      'Internal': 'badge-info',
      'External': 'badge-purple',
      'Practical': 'badge-warning',
      'Viva': 'badge-slate'
    };
    return <span className={`badge ${map[type] || 'badge-slate'}`}>{type}</span>;
  };

  const getStatusBadge = (status) => {
    const map = {
      'Scheduled': 'badge-info',
      'Completed': 'badge-success',
      'Cancelled': 'badge-danger'
    };
    return <span className={`badge ${map[status] || 'badge-slate'}`}>{status}</span>;
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Exam Schedule</h1>
          <p className="page-subtitle">View and manage upcoming examinations</p>
        </div>
        {isStaff && (
          <div className="flex gap-2">
            <Link to="/exams/add" className="btn btn-primary">
              <PlusIcon /> Add Exam
            </Link>
          </div>
        )}
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Exam Title</th>
              <th>Subject</th>
              <th>Class</th>
              <th>Date & Time</th>
              <th>Venue</th>
              <th>Type</th>
              <th>Status</th>
              {isStaff && <th>Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: isStaff ? 8 : 7 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : exams.length === 0 ? (
              <tr>
                <td colSpan={isStaff ? 8 : 7} className="text-center py-12 text-slate-500">
                  No exams scheduled.
                </td>
              </tr>
            ) : (
              exams.map(e => (
                <tr key={e._id}>
                  <td className="text-white font-medium text-sm">{e.exam_title}</td>
                  <td className="text-indigo-400 text-sm">{e.subject?.subject_name}</td>
                  <td className="text-slate-300 text-sm">{e.class?.class_name} {e.class?.section ? `(${e.class?.section})` : ''}</td>
                  <td>
                    <p className="text-sm text-slate-200">{new Date(e.exam_date).toLocaleDateString()}</p>
                    <p className="text-xs text-slate-400 font-mono mt-0.5">{e.start_time} - {e.end_time}</p>
                  </td>
                  <td className="text-slate-300 text-sm">{e.venue}</td>
                  <td>{getTypeBadge(e.exam_type)}</td>
                  <td>
                    {isStaff ? (
                      <select 
                        className="input py-1 px-2 text-xs h-auto min-h-[30px]" 
                        value={e.status}
                        onChange={(ev) => handleStatusChange(e._id, ev.target.value)}
                      >
                        <option value="Scheduled">Scheduled</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                      </select>
                    ) : (
                      getStatusBadge(e.status)
                    )}
                  </td>
                  {isStaff && (
                    <td>
                      <button onClick={() => setDeleteTarget(e)} className="btn-icon btn-ghost text-slate-400 hover:text-red-400" title="Delete">
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

export default ExamsPage;