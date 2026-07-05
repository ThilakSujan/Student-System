import { useEffect, useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAttendance } from '../../api/attendance';
import { useAuth } from '../../context/AuthContext';

const CheckIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-5 h-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
  </svg>
);
const SearchIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
  </svg>
);
const CalendarIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
  </svg>
);

const AttendancePage = () => {
  const { user } = useAuth();
  const isAdminOrStaff = user?.role === 'admin' || user?.role === 'staff';
  
  const [records, setRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const today = new Date().toISOString().split('T')[0];
  const [date, setDate] = useState(today);
  const [search, setSearch] = useState('');

  const fetchRecords = useCallback(() => {
    setLoading(true);
    // Student sees all their records for the month if date isn't filtered strictly, 
    // but the API supports filtering by date. We filter by date.
    getAttendance({ date })
      .then(res => setRecords(res.data.data || []))
      .catch(() => toast.error('Failed to load attendance'))
      .finally(() => setLoading(false));
  }, [date]);

  useEffect(() => { fetchRecords(); }, [fetchRecords]);

  const filtered = records.filter(r => {
    const term = search.toLowerCase();
    return !search || r.student?.student_name?.toLowerCase().includes(term);
  });

  const getStatusBadge = (status) => {
    const map = {
      Present: 'badge-success',
      Absent: 'badge-danger',
      Late: 'badge-warning',
      Excused: 'badge-info'
    };
    return <span className={`badge ${map[status] || 'badge-slate'}`}>{status}</span>;
  };

  return (
    <div className="space-y-6 animate-fade-in">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="page-title">Attendance Records</h1>
          <p className="page-subtitle">View daily student attendance</p>
        </div>
        {isAdminOrStaff && (
          <div className="flex gap-2">
            <Link to="/attendance/mark" className="btn btn-primary">
              <CheckIcon /> Mark Attendance
            </Link>
          </div>
        )}
      </div>

      <div className="card p-4 flex flex-col sm:flex-row gap-4">
        <div className="form-group flex-1 max-w-xs mb-0">
          <label className="label text-xs">Select Date</label>
          <div className="relative">
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><CalendarIcon /></span>
            <input
              type="date"
              className="input pl-9"
              value={date}
              onChange={e => setDate(e.target.value)}
            />
          </div>
        </div>

        {isAdminOrStaff && (
          <div className="form-group flex-1 max-w-md mb-0">
            <label className="label text-xs">Search Student</label>
            <div className="relative">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"><SearchIcon /></span>
              <input
                type="text"
                className="input pl-9"
                placeholder="Search by name..."
                value={search}
                onChange={e => setSearch(e.target.value)}
              />
            </div>
          </div>
        )}
      </div>

      <div className="table-container">
        <table className="table">
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Date</th>
              <th>Status</th>
              {isAdminOrStaff && <th>Marked At</th>}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              Array.from({ length: 3 }).map((_, i) => (
                <tr key={i}>
                  {Array.from({ length: isAdminOrStaff ? 4 : 3 }).map((_, j) => (
                    <td key={j}><div className="skeleton h-4 w-full" /></td>
                  ))}
                </tr>
              ))
            ) : filtered.length === 0 ? (
              <tr>
                <td colSpan={isAdminOrStaff ? 4 : 3} className="text-center py-12 text-slate-500">
                  {search ? 'No students found.' : 'No attendance records found for this date.'}
                </td>
              </tr>
            ) : (
              filtered.map(r => (
                <tr key={r._id}>
                  <td className="text-white font-medium text-sm">{r.student?.student_name || 'Unknown'}</td>
                  <td className="text-slate-300 text-sm">{new Date(r.date).toLocaleDateString()}</td>
                  <td>{getStatusBadge(r.status)}</td>
                  {isAdminOrStaff && <td className="text-slate-400 text-xs">{new Date(r.updatedAt).toLocaleTimeString()}</td>}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default AttendancePage;