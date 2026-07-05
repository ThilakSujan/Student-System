import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getFeePayments } from '../../api/fees';
import { useAuth } from '../../context/AuthContext';

const CurrencyIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-8 h-8">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 8.25H9m6 3H9m3 6-3-3h1.5a3 3 0 1 0 0-6M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
  </svg>
);
const DocumentIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-8 h-8">
    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
  </svg>
);

const FeePage = () => {
  const { user } = useAuth();
  const isStaff = user?.role === 'admin' || user?.role === 'staff';
  
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getFeePayments()
      .then(res => setPayments(res.data.data || []))
      .catch(() => toast.error('Failed to load fee data'))
      .finally(() => setLoading(false));
  }, []);

  const totalCollected = payments.reduce((sum, p) => sum + p.amount_paid, 0);
  const recentPayments = payments.slice(0, 5); // Assuming already sorted descending

  return (
    <div className="space-y-6 animate-fade-in max-w-6xl">
      <div>
        <h1 className="page-title">Fee Management</h1>
        <p className="page-subtitle">Overview of fee collections and structure</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-slate-400 text-sm font-medium">Total Collected</p>
              <h2 className="text-3xl font-bold text-white mt-1">₹ {totalCollected.toLocaleString()}</h2>
            </div>
            <div className="p-3 bg-emerald-500/10 rounded-xl text-emerald-400">
              <CurrencyIcon />
            </div>
          </div>
        </div>
        <div className="stat-card">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-slate-400 text-sm font-medium">Total Transactions</p>
              <h2 className="text-3xl font-bold text-white mt-1">{payments.length}</h2>
            </div>
            <div className="p-3 bg-indigo-500/10 rounded-xl text-indigo-400">
              <DocumentIcon />
            </div>
          </div>
        </div>
      </div>

      {isStaff && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Link to="/fee/payments" className="btn btn-primary justify-center py-3">View All Payments</Link>
          <Link to="/fee/categories" className="btn btn-secondary justify-center py-3">Fee Categories</Link>
          <Link to="/fee/structures" className="btn btn-secondary justify-center py-3">Fee Structures</Link>
          <Link to="/fee/report" className="btn btn-secondary justify-center py-3 text-indigo-400">Generate Report</Link>
        </div>
      )}

      <div className="card">
        <div className="card-header flex justify-between items-center">
          <h2 className="section-title">Recent Transactions</h2>
          {!isStaff && <Link to="/fee/payments" className="text-indigo-400 text-sm hover:underline">View All</Link>}
        </div>
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Receipt No</th>
                {isStaff && <th>Student</th>}
                <th>Category</th>
                <th>Amount</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {loading ? (
                <tr><td colSpan={5} className="text-center py-8"><div className="skeleton h-4 w-1/2 mx-auto" /></td></tr>
              ) : recentPayments.length === 0 ? (
                <tr><td colSpan={5} className="text-center py-8 text-slate-500">No recent transactions.</td></tr>
              ) : (
                recentPayments.map(p => (
                  <tr key={p._id}>
                    <td className="font-mono text-sm text-indigo-400">{p.receipt_no}</td>
                    {isStaff && <td className="text-sm text-white">{p.student?.student_name}</td>}
                    <td className="text-sm text-slate-300">{p.feeStructure?.category?.name || '—'}</td>
                    <td className="text-sm font-mono text-emerald-400">₹ {p.amount_paid.toLocaleString()}</td>
                    <td className="text-sm text-slate-400">{new Date(p.payment_date).toLocaleDateString()}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default FeePage;