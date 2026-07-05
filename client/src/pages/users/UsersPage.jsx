import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getPendingUsers, approveUser, rejectUser } from '../../api/users';
import { useAuth } from '../../context/AuthContext';

const CheckIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
  </svg>
);
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);
const CloseIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

const RejectModal = ({ user, onClose, onConfirm }) => {
  const [reason, setReason] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!reason.trim()) { toast.error('Reason is required'); return; }
    setSubmitting(true);
    await onConfirm(user._id, reason);
    setSubmitting(false);
  };

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-box max-w-sm" onClick={e => e.stopPropagation()}>
        <div className="card-header flex items-center justify-between">
          <h3 className="section-title text-red-400">Reject Registration</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><CloseIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body space-y-4">
          <p className="text-slate-300 text-sm">
            Reject registration for <span className="text-white font-semibold">{user.username}</span> ({user.email})?
          </p>
          <div className="form-group">
            <label className="label">Rejection Reason *</label>
            <textarea 
              className="input h-24" 
              placeholder="Explain why this account was rejected..."
              value={reason}
              onChange={e => setReason(e.target.value)}
            />
          </div>
          <div className="flex gap-3 justify-end">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-danger" disabled={submitting}>
              {submitting ? 'Rejecting...' : 'Reject Account'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const UsersPage = () => {
  const { user: currentUser } = useAuth();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [rejectTarget, setRejectTarget] = useState(null);

  const fetchPending = useCallback(() => {
    setLoading(true);
    getPendingUsers()
      .then(res => setUsers(res.data.data || []))
      .catch(() => toast.error('Failed to load pending users'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchPending(); }, [fetchPending]);

  const handleApprove = async (id) => {
    try {
      await approveUser(id);
      toast.success('Account approved! Notification email sent.');
      fetchPending();
    } catch {
      toast.error('Failed to approve user');
    }
  };

  const handleReject = async (id, reason) => {
    try {
      await rejectUser(id, { reason });
      toast.success('Account rejected. Notification email sent.');
      setRejectTarget(null);
      fetchPending();
    } catch {
      toast.error('Failed to reject user');
    }
  };

  if (currentUser?.role !== 'admin') {
    return <div className="p-12 text-center text-red-400">Access Denied. Admins only.</div>;
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl">
      <div>
        <h1 className="page-title">Pending Approvals</h1>
        <p className="page-subtitle">Review new staff registration requests</p>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>User</th>
              <th>Role requested</th>
              <th>Date Applied</th>
              <th className="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: 4 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={4} className="text-center py-16">
                  <div className="flex flex-col items-center justify-center text-slate-500">
                    <CheckIcon className="w-12 h-12 text-emerald-500/20 mb-3" />
                    <p className="text-lg">All caught up!</p>
                    <p className="text-sm">No pending registration requests.</p>
                  </div>
                </td>
              </tr>
            ) : (
              users.map(u => (
                <tr key={u._id}>
                  <td>
                    <p className="text-white font-medium text-sm">{u.username}</p>
                    <p className="text-slate-400 text-xs">{u.email}</p>
                  </td>
                  <td><span className="badge badge-info">{u.role}</span></td>
                  <td className="text-slate-300 text-sm">{new Date(u.created_at).toLocaleString()}</td>
                  <td>
                    <div className="flex items-center justify-end gap-2">
                      <button 
                        onClick={() => setRejectTarget(u)}
                        className="btn btn-sm btn-ghost text-red-400 hover:bg-red-400/10"
                      >
                        <XMarkIcon /> Reject
                      </button>
                      <button 
                        onClick={() => handleApprove(u._id)}
                        className="btn btn-sm btn-primary bg-emerald-600 hover:bg-emerald-500"
                      >
                        <CheckIcon /> Approve
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {rejectTarget && (
        <RejectModal
          user={rejectTarget}
          onClose={() => setRejectTarget(null)}
          onConfirm={handleReject}
        />
      )}
    </div>
  );
};

export default UsersPage;