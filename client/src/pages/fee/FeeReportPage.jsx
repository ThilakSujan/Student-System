import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getFeePayments } from '../../api/fees';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);
const ExportIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
  </svg>
);

const FeeReportPage = () => {
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');

  useEffect(() => {
    getFeePayments()
      .then(res => setPayments(res.data.data || []))
      .catch(() => toast.error('Failed to load data'))
      .finally(() => setLoading(false));
  }, []);

  const filtered = payments.filter(p => {
    const d = new Date(p.payment_date);
    const start = fromDate ? new Date(fromDate) : null;
    const end = toDate ? new Date(toDate) : null;
    
    if (start && d < start) return false;
    if (end && d > end) return false;
    return true;
  });

  const total = filtered.reduce((s, p) => s + p.amount_paid, 0);

  const handleExport = () => {
    toast.success('Exporting report as PDF...');
    // Real export logic would go here
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl pb-10">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <Link to="/fee" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
            <BackIcon /> Back
          </Link>
          <div>
            <h1 className="page-title">Fee Collection Report</h1>
            <p className="page-subtitle">View and export fee collections</p>
          </div>
        </div>
        <button onClick={handleExport} className="btn btn-primary">
          <ExportIcon /> Export PDF
        </button>
      </div>

      <div className="card p-4 flex flex-wrap gap-4">
        <div className="form-group mb-0 flex-1 min-w-[200px]">
          <label className="label text-xs">From Date</label>
          <input type="date" className="input" value={fromDate} onChange={e => setFromDate(e.target.value)} />
        </div>
        <div className="form-group mb-0 flex-1 min-w-[200px]">
          <label className="label text-xs">To Date</label>
          <input type="date" className="input" value={toDate} onChange={e => setToDate(e.target.value)} />
        </div>
      </div>

      <div className="card">
        <div className="card-header bg-slate-900 flex justify-between items-center">
          <h2 className="section-title">Collection Data</h2>
          <span className="text-emerald-400 font-bold font-mono">Total: ₹ {total.toLocaleString()}</span>
        </div>
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Receipt No</th>
                <th>Student</th>
                <th>Category</th>
                <th>Mode</th>
                <th className="text-right">Amount (₹)</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan={6} className="text-center py-12"><div className="skeleton h-4 w-1/2 mx-auto" /></td></tr>
              ) : filtered.length === 0 ? (
                <tr><td colSpan={6} className="text-center py-12 text-slate-500">No collections found for this period.</td></tr>
              ) : (
                filtered.map(p => (
                  <tr key={p._id}>
                    <td className="text-sm text-slate-300">{new Date(p.payment_date).toLocaleDateString()}</td>
                    <td className="text-sm font-mono text-indigo-400">{p.receipt_no}</td>
                    <td className="text-sm text-white font-medium">{p.student?.student_name}</td>
                    <td className="text-sm text-slate-400">{p.feeStructure?.category?.name || '—'}</td>
                    <td className="text-sm text-slate-300">{p.payment_mode}</td>
                    <td className="text-sm font-mono text-emerald-400 text-right">{p.amount_paid.toLocaleString()}</td>
                  </tr>
                ))
              )}
            </tbody>
            {filtered.length > 0 && (
              <tfoot>
                <tr className="bg-slate-900 border-t-2 border-slate-700">
                  <td colSpan={5} className="text-right font-bold text-white py-3 pr-4">Total Collected:</td>
                  <td className="text-right font-bold text-emerald-400 font-mono py-3 pr-4">₹ {total.toLocaleString()}</td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>
    </div>
  );
};

export default FeeReportPage;