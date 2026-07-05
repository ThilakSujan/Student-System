import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getNotifications, createNotification, deleteNotification, updateNotification } from '../../api/notifications';
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
const BellIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5 text-indigo-400 mt-1">
    <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
  </svg>
);

const NoticeModal = ({ onClose, onSave }) => {
  const [form, setForm] = useState({
    title: '',
    message: '',
    target_audience: 'Both',
    expiry_date: '',
    status: 'Active'
  });
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.title.trim()) errs.title = 'Title required';
    if (!form.message.trim()) errs.message = 'Message required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      const data = { ...form };
      if (!data.expiry_date) delete data.expiry_date;
      await createNotification(data);
      toast.success('Notification posted');
      onSave();
    } catch (err) {
      toast.error('Failed to post notification');
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
          <h3 className="section-title">Post Announcement</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><XMarkIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body grid grid-cols-1 gap-4">
          <div className="form-group">
            <label htmlFor="title" className="label">Title *</label>
            <input id="title" className={`input ${errors.title ? 'input-error' : ''}`} placeholder="Announcement title" value={form.title} onChange={set('title')} />
            {errors.title && <p className="error-msg">{errors.title}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="msg" className="label">Message *</label>
            <textarea id="msg" className={`input h-32 ${errors.message ? 'input-error' : ''}`} placeholder="Write your message here..." value={form.message} onChange={set('message')} />
            {errors.message && <p className="error-msg">{errors.message}</p>}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div className="form-group">
              <label htmlFor="target" className="label">Audience</label>
              <select id="target" className="input" value={form.target_audience} onChange={set('target_audience')}>
                <option value="Both">Both (Staff & Students)</option>
                <option value="Staff">Staff Only</option>
                <option value="Student">Students Only</option>
              </select>
            </div>
            <div className="form-group">
              <label htmlFor="exp" className="label">Expiry Date (Optional)</label>
              <input id="exp" type="date" className="input" value={form.expiry_date} onChange={set('expiry_date')} />
            </div>
          </div>
          <div className="flex justify-end gap-3 pt-2 border-t border-slate-700/50 mt-2">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Posting...' : 'Post Announcement'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const NotificationsPage = () => {
  const { user } = useAuth();
  const isAdminOrStaff = user?.role === 'admin' || user?.role === 'staff';
  
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);

  const fetchNotes = useCallback(() => {
    setLoading(true);
    getNotifications()
      .then(res => setNotifications(res.data.data || []))
      .catch(() => toast.error('Failed to load announcements'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => { fetchNotes(); }, [fetchNotes]);

  const handleDelete = async (id) => {
    if (!window.confirm('Delete this announcement?')) return;
    try {
      await deleteNotification(id);
      toast.success('Deleted');
      fetchNotes();
    } catch {
      toast.error('Failed to delete');
    }
  };

  const handleToggle = async (id, currentStatus) => {
    try {
      const nextStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
      await updateNotification(id, { status: nextStatus });
      toast.success('Status updated');
      fetchNotes();
    } catch {
      toast.error('Failed to update');
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Notice Board</h1>
          <p className="page-subtitle">Latest announcements and updates</p>
        </div>
        {isAdminOrStaff && (
          <div className="flex gap-2">
            <button onClick={() => setShowModal(true)} className="btn btn-primary">
              <PlusIcon /> Post Announcement
            </button>
          </div>
        )}
      </div>

      <div className="space-y-4">
        {loading ? (
          Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="card p-6"><div className="skeleton h-24 w-full" /></div>
          ))
        ) : notifications.length === 0 ? (
          <div className="card p-12 text-center text-slate-500">
            No announcements to show.
          </div>
        ) : (
          notifications.map(n => {
            const isExpired = n.expiry_date && new Date(n.expiry_date) < new Date();
            const isActive = n.status === 'Active' && !isExpired;
            
            return (
              <div key={n._id} className={`card p-5 relative overflow-hidden ${!isActive ? 'opacity-60' : ''}`}>
                <div className="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500" />
                <div className="flex justify-between items-start gap-4">
                  <div className="flex gap-3">
                    <BellIcon />
                    <div>
                      <h3 className="text-lg font-semibold text-white">{n.title}</h3>
                      <div className="flex items-center gap-2 mt-1 mb-3">
                        <span className="text-xs text-slate-400">By {n.created_by?.full_name || n.created_by?.username}</span>
                        <span className="text-slate-600">•</span>
                        <span className="text-xs text-slate-400">{new Date(n.created_at).toLocaleDateString()}</span>
                        <span className="text-slate-600">•</span>
                        <span className="badge badge-slate text-xs">{n.target_audience}</span>
                        {isExpired && <span className="badge badge-danger text-xs">Expired</span>}
                      </div>
                      <p className="text-slate-300 whitespace-pre-wrap">{n.message}</p>
                    </div>
                  </div>
                  
                  {isAdminOrStaff && (
                    <div className="flex flex-col items-end gap-2 flex-shrink-0">
                      <div className="flex gap-1">
                        <button 
                          onClick={() => handleToggle(n._id, n.status)}
                          className={`btn-icon btn-ghost text-xs ${n.status === 'Active' ? 'text-emerald-400' : 'text-slate-400'}`}
                          title="Toggle Status"
                        >
                          {n.status === 'Active' ? 'Active' : 'Inactive'}
                        </button>
                        <button onClick={() => handleDelete(n._id)} className="btn-icon btn-ghost text-slate-400 hover:text-red-400" title="Delete">
                          <TrashIcon />
                        </button>
                      </div>
                      {n.expiry_date && (
                        <span className="text-[10px] text-slate-500 mt-2">
                          Expires: {new Date(n.expiry_date).toLocaleDateString()}
                        </span>
                      )}
                    </div>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>

      {showModal && (
        <NoticeModal 
          onClose={() => setShowModal(false)}
          onSave={() => { setShowModal(false); fetchNotes(); }}
        />
      )}
    </div>
  );
};

export default NotificationsPage;