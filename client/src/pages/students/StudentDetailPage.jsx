import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getStudent, deleteStudent } from '../../api/students';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
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

const InfoRow = ({ label, value }) => (
  <div className="flex flex-col sm:flex-row sm:items-center gap-1 py-3 border-b border-slate-700/50 last:border-0">
    <span className="text-slate-500 text-sm w-40 flex-shrink-0">{label}</span>
    <span className="text-slate-200 text-sm font-medium">{value || '—'}</span>
  </div>
);

const StatusBadge = ({ status }) => {
  const map = { Active: 'badge-success', Inactive: 'badge-slate', Graduated: 'badge-info', Expelled: 'badge-danger' };
  return <span className={`badge ${map[status] || 'badge-slate'}`}>{status}</span>;
};

const Avatar = ({ name }) => {
  const initials = name?.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() || '?';
  const colors = ['from-indigo-500 to-indigo-700', 'from-emerald-500 to-emerald-700', 'from-amber-500 to-amber-700', 'from-pink-500 to-rose-700', 'from-purple-500 to-purple-700'];
  const color = colors[initials.charCodeAt(0) % colors.length];
  return (
    <div className={`w-20 h-20 rounded-2xl bg-gradient-to-br ${color} flex items-center justify-center text-white text-2xl font-bold`}>
      {initials}
    </div>
  );
};

const StudentDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [student, setStudent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => {
    getStudent(id)
      .then(res => setStudent(res.data.data))
      .catch(() => toast.error('Failed to load student'))
      .finally(() => setLoading(false));
  }, [id]);

  const handleDelete = async () => {
    try {
      await deleteStudent(id);
      toast.success('Student deleted successfully');
      navigate('/students');
    } catch {
      toast.error('Failed to delete student');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!student) {
    return (
      <div className="card p-12 text-center text-slate-400">
        Student not found. <Link to="/students" className="text-indigo-400 hover:underline">Go back</Link>
      </div>
    );
  }

  const age = student.dob
    ? Math.floor((new Date() - new Date(student.dob)) / (365.25 * 24 * 60 * 60 * 1000))
    : null;

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl">
      {/* Header */}
      <div className="flex items-center justify-between gap-4 flex-wrap">
        <div className="flex items-center gap-4">
          <Link to="/students" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
            <BackIcon /> Back
          </Link>
          <div>
            <h1 className="page-title">Student Profile</h1>
            <p className="page-subtitle">Viewing details for {student.student_name}</p>
          </div>
        </div>
        <div className="flex gap-2">
          <Link to={`/students/${id}/edit`} className="btn btn-secondary">
            <EditIcon /> Edit
          </Link>
          <button className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
            <TrashIcon /> Delete
          </button>
        </div>
      </div>

      {/* Profile Hero */}
      <div className="card p-6 flex flex-col sm:flex-row items-center sm:items-start gap-6">
        <Avatar name={student.student_name} />
        <div className="flex-1 text-center sm:text-left">
          <h2 className="text-2xl font-bold text-white">{student.student_name}</h2>
          <p className="text-slate-400 text-sm mt-1">{student.email}</p>
          <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2 mt-3">
            <StatusBadge status={student.status} />
            {student.department && <span className="badge badge-purple">{student.department}</span>}
            {student.gender && <span className="badge badge-info">{student.gender}</span>}
            {age && <span className="badge badge-slate">{age} years old</span>}
          </div>
        </div>
        <div className="text-center">
          <p className="text-xs text-slate-500">Enrolled</p>
          <p className="text-sm text-slate-300 font-medium">
            {student.createdAt ? new Date(student.createdAt).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'}
          </p>
        </div>
      </div>

      {/* Details grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Personal */}
        <div className="card">
          <div className="card-header"><h2 className="section-title">Personal Details</h2></div>
          <div className="card-body">
            <InfoRow label="Full Name" value={student.student_name} />
            <InfoRow label="Email" value={student.email} />
            <InfoRow label="Phone" value={student.phone} />
            <InfoRow label="Gender" value={student.gender} />
            <InfoRow label="Date of Birth" value={student.dob ? new Date(student.dob).toLocaleDateString() : null} />
            <InfoRow label="Age" value={age ? `${age} years` : null} />
          </div>
        </div>

        {/* Academic */}
        <div className="card">
          <div className="card-header"><h2 className="section-title">Academic Details</h2></div>
          <div className="card-body">
            <InfoRow label="Department" value={student.department} />
            <InfoRow label="Status" value={<StatusBadge status={student.status} />} />
            <InfoRow label="Parent Name" value={student.parent_name} />
            <InfoRow label="Parent Email" value={student.parent_email} />
            <div className="py-3">
              <span className="text-slate-500 text-sm">Skills / Interests</span>
              <div className="flex flex-wrap gap-2 mt-2">
                {student.skills?.length > 0
                  ? student.skills.map(skill => (
                      <span key={skill} className="badge badge-purple">{skill}</span>
                    ))
                  : <span className="text-slate-600 text-sm">None listed</span>
                }
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Quick links */}
      <div className="card p-4">
        <h2 className="section-title mb-3">Quick Links</h2>
        <div className="flex flex-wrap gap-2">
          <Link to={`/students/${id}/report-card`} className="btn btn-secondary btn-sm">📄 Report Card</Link>
          <Link to="/attendance" className="btn btn-secondary btn-sm">📅 Attendance</Link>
          <Link to="/marks" className="btn btn-secondary btn-sm">📝 Marks</Link>
          <Link to="/fees/payments" className="btn btn-secondary btn-sm">💰 Fee Payments</Link>
        </div>
      </div>

      {/* Delete Modal */}
      {confirmDelete && (
        <div className="modal-backdrop" onClick={() => setConfirmDelete(false)}>
          <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
            <div className="card-header">
              <h3 className="section-title text-red-400">Delete Student</h3>
            </div>
            <div className="card-body space-y-4">
              <p className="text-slate-300 text-sm">
                Are you sure you want to permanently delete <span className="text-white font-semibold">{student.student_name}</span>?
              </p>
              <div className="flex gap-3 justify-end">
                <button onClick={() => setConfirmDelete(false)} className="btn btn-secondary">Cancel</button>
                <button onClick={handleDelete} className="btn btn-danger">Delete</button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default StudentDetailPage;
