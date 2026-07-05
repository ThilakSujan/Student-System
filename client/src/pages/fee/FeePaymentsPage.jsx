import { useEffect, useState, useCallback } from 'react';
import { toast } from 'react-hot-toast';
import { getFeePayments, createFeePayment, deleteFeePayment, getFeeStructures } from '../../api/fees';
import { getAllStudents } from '../../api/students';
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
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

const PaymentModal = ({ students, structures, onClose, onSave }) => {
  const [form, setForm] = useState({
    student: '',
    feeStructure: '',
    amount_paid: '',
    payment_mode: 'Cash',
    payment_date: new Date().toISOString().split('T')[0],
    remarks: ''
  });
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  // Auto-fill amount when structure changes
  useEffect(() => {
    if (form.feeStructure) {
      const struct = structures.find(s => s._id === form.feeStructure);
      if (struct) setForm(p => ({ ...p, amount_paid: struct.amount }));
    }
  }, [form.feeStructure, structures]);

  const validate = () => {
    const errs = {};
    if (!form.student) errs.student = 'Student required';
    if (!form.feeStructure) errs.feeStructure = 'Structure required';
    if (!form.amount_paid || isNaN(form.amount_paid) || form.amount_paid <= 0) errs.amount_paid = 'Valid amount required';
    if (!form.payment_date) errs.payment_date = 'Date required';
    return errs;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const errs = validate();
    if (Object.keys(errs).length) { setErrors(errs); return; }

    setSaving(true);
    try {
      await createFeePayment({ ...form, amount_paid: Number(form.amount_paid) });
      toast.success('Payment recorded successfully');
      onSave();
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to record payment');
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
          <h3 className="section-title">Record Fee Payment</h3>
          <button onClick={onClose} className="btn-icon btn-ghost"><XMarkIcon /></button>
        </div>
        <form onSubmit={handleSubmit} className="card-body grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div className="form-group sm:col-span-2">
            <label htmlFor="stu" className="label">Student *</label>
            <select id="stu" className={`input ${errors.student ? 'input-error' : ''}`} value={form.student} onChange={set('student')}>
              <option value="">-- Select Student --</option>
              {students.map(s => <option key={s._id} value={s._id}>{s.student_name} ({s.email})</option>)}
            </select>
            {errors.student && <p className="error-msg">{errors.student}</p>}
          </div>
          <div className="form-group sm:col-span-2">
            <label htmlFor="str" className="label">Fee Structure *</label>
            <select id="str" className={`input ${errors.feeStructure ? 'input-error' : ''}`} value={form.feeStructure} onChange={set('feeStructure')}>
              <option value="">-- Select Structure --</option>
              {structures.map(s => <option key={s._id} value={s._id}>{s.category?.name} (₹{s.amount})</option>)}
            </select>
            {errors.feeStructure && <p className="error-msg">{errors.feeStructure}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="amt" className="label">Amount Paid (₹) *</label>
            <input id="amt" type="number" min="0" className={`input ${errors.amount_paid ? 'input-error' : ''}`} value={form.amount_paid} onChange={set('amount_paid')} />
            {errors.amount_paid && <p className="error-msg">{errors.amount_paid}</p>}
          </div>
          <div className="form-group">
            <label htmlFor="pmt" className="label">Payment Mode *</label>
            <select id="pmt" className="input" value={form.payment_mode} onChange={set('payment_mode')}>
              <option value="Cash">Cash</option>
              <option value="Online">Online</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cheque">Cheque</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="date" className="label">Payment Date *</label>
            <input id="date" type="date" className={`input ${errors.payment_date ? 'input-error' : ''}`} value={form.payment_date} onChange={set('payment_date')} />
            {errors.payment_date && <p className="error-msg">{errors.payment_date}</p>}
          </div>
          <div className="form-group sm:col-span-2">
            <label htmlFor="rem" className="label">Remarks</label>
            <input id="rem" className="input" value={form.remarks} onChange={set('remarks')} />
          </div>
          
          <div className="flex justify-end gap-3 pt-2 sm:col-span-2 border-t border-slate-700/50 mt-2">
            <button type="button" onClick={onClose} className="btn btn-secondary">Cancel</button>
            <button type="submit" className="btn btn-primary" disabled={saving}>
              {saving ? 'Saving...' : 'Record Payment'}
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
        <h3 className="section-title text-red-400">Delete Payment</h3>
        <button onClick={onCancel} className="btn-icon btn-ghost"><XMarkIcon /></button>
      </div>
      <div className="card-body space-y-4">
        <p className="text-slate-300 text-sm">
          Are you sure you want to delete receipt <span className="text-white font-mono">{item.receipt_no}</span> for <span className="text-white">{item.student?.student_name}</span>? 
        </p>
        <div className="flex gap-3 justify-end">
          <button onClick={onCancel} className="btn btn-secondary">Cancel</button>
          <button onClick={onConfirm} className="btn btn-danger">Delete</button>
        </div>
      </div>
    </div>
  </div>
);

const FeePaymentsPage = () => {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';
  const isStaff = user?.role === 'staff' || isAdmin;
  
  const [payments, setPayments] = useState([]);
  const [students, setStudents] = useState([]);
  const [structures, setStructures] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const [search, setSearch] = useState('');
  const [showModal, setShowModal] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const fetchData = useCallback(() => {
    setLoading(true);
    const promises = [getFeePayments()];
    if (isStaff) {
      promises.push(getAllStudents());
      promises.push(getFeeStructures());
    }
    Promise.all(promises)
      .then(responses => {
        setPayments(responses[0].data.data || []);
        if (isStaff) {
          setStudents((responses[1].data.data || []).filter(s => s.status === 'Active'));
          setStructures((responses[2].data.data || []).filter(s => s.status === 'Active'));
        }
      })
      .catch(() => toast.error('Failed to load data'))
      .finally(() => setLoading(false));
  }, [isStaff]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const handleDelete = async () => {
    try {
      await deleteFeePayment(deleteTarget._id);
      toast.success('Payment deleted successfully');
      setDeleteTarget(null);
      fetchData();
    } catch {
      toast.error('Failed to delete payment');
    }
  };

  const filtered = payments.filter(p => {
    const term = search.toLowerCase();
    return !search || 
      p.receipt_no.toLowerCase().includes(term) || 
      p.student?.student_name?.toLowerCase().includes(term);
  });

  const getModeBadge = (mode) => {
    const map = {
      'Cash': 'badge-success',
      'Online': 'badge-info',
      'Cheque': 'badge-warning',
      'Bank Transfer': 'badge-purple'
    };
    return <span className={`badge ${map[mode] || 'badge-slate'}`}>{mode}</span>;
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Fee Payments</h1>
          <p className="page-subtitle">View and manage fee transactions</p>
        </div>
        {isStaff && (
          <div className="flex gap-2">
            <button onClick={() => setShowModal(true)} className="btn btn-primary">
              <PlusIcon /> Record Payment
            </button>
          </div>
        )}
      </div>

      <div className="card p-4">
        <div className="relative max-w-md">
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
          <input
            type="text"
            className="input pl-9"
            placeholder="Search by receipt no or student name…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
        </div>
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Receipt No</th>
              <th>Student</th>
              <th>Category</th>
              <th>Amount (₹)</th>
              <th>Mode</th>
              <th>Date</th>
              {isAdmin && <th>Actions</th>}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: isAdmin ? 7 : 6 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={isAdmin ? 7 : 6} className="text-center py-12 text-slate-500">
                  {search ? 'No payments match your search.' : 'No payments found.'}
                </td>
              </tr>
            ) : (
              filtered.map(p => (
                <tr key={p._id}>
                  <td className="text-indigo-400 font-mono text-sm">{p.receipt_no}</td>
                  <td className="text-white font-medium text-sm">{p.student?.student_name || '—'}</td>
                  <td className="text-slate-300 text-sm">{p.feeStructure?.category?.name || '—'}</td>
                  <td className="text-emerald-400 font-mono text-sm font-semibold">₹ {p.amount_paid.toLocaleString()}</td>
                  <td>{getModeBadge(p.payment_mode)}</td>
                  <td className="text-slate-300 text-sm">{new Date(p.payment_date).toLocaleDateString()}</td>
                  {isAdmin && (
                    <td>
                      <button onClick={() => setDeleteTarget(p)} className="btn-icon btn-ghost text-slate-400 hover:text-red-400" title="Delete">
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

      {showModal && (
        <PaymentModal 
          students={students}
          structures={structures}
          onClose={() => setShowModal(false)}
          onSave={() => { setShowModal(false); fetchData(); }}
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

export default FeePaymentsPage;