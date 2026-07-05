import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAllStudents } from '../../api/students';
import { markAttendance } from '../../api/attendance';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const Avatar = ({ name }) => {
  const initials = (name || '?').split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
  return (
    <div className="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
      {initials}
    </div>
  );
};

const AttendanceMarkPage = () => {
  const navigate = useNavigate();
  const [students, setStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  
  const today = new Date().toISOString().split('T')[0];
  const [date, setDate] = useState(today);
  const [attendance, setAttendance] = useState({}); // { studentId: 'Present' | 'Absent' | ... }

  useEffect(() => {
    setLoading(true);
    getAllStudents()
      .then(res => {
        const active = (res.data.data || []).filter(s => s.status === 'Active');
        setStudents(active);
        // Default everyone to present
        const initial = {};
        active.forEach(s => initial[s._id] = 'Present');
        setAttendance(initial);
      })
      .catch(() => toast.error('Failed to load students'))
      .finally(() => setLoading(false));
  }, []);

  const handleStatusChange = (id, status) => {
    setAttendance(prev => ({ ...prev, [id]: status }));
  };

  const setAll = (status) => {
    const next = {};
    students.forEach(s => next[s._id] = status);
    setAttendance(next);
  };

  const handleSubmit = async () => {
    setSaving(true);
    try {
      const records = Object.keys(attendance).map(studentId => ({
        studentId,
        status: attendance[studentId]
      }));
      
      await markAttendance({ date, records });
      toast.success('Attendance saved successfully!');
      navigate('/attendance');
    } catch (err) {
      toast.error('Failed to save attendance');
    } finally {
      setSaving(false);
    }
  };

  const options = ['Present', 'Absent', 'Late', 'Excused'];
  const colors = { Present: 'bg-emerald-500', Absent: 'bg-red-500', Late: 'bg-amber-500', Excused: 'bg-blue-500' };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-4xl mx-auto pb-24">
      <div className="flex items-center gap-4">
        <Link to="/attendance" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Mark Attendance</h1>
          <p className="page-subtitle">Record daily attendance for all students</p>
        </div>
      </div>

      <div className="card p-5 flex flex-col sm:flex-row justify-between items-center gap-4 sticky top-4 z-10 shadow-xl border-indigo-500/30">
        <div className="form-group mb-0 max-w-xs w-full">
          <label htmlFor="date" className="label text-xs">Attendance Date</label>
          <input id="date" type="date" className="input" value={date} onChange={e => setDate(e.target.value)} />
        </div>
        
        <div className="flex gap-2">
          <button onClick={() => setAll('Present')} className="btn btn-sm btn-ghost text-emerald-400 border border-emerald-400/30">Mark All Present</button>
          <button onClick={() => setAll('Absent')} className="btn btn-sm btn-ghost text-red-400 border border-red-400/30">Mark All Absent</button>
        </div>
      </div>

      <div className="space-y-3">
        {students.length === 0 ? (
          <div className="card p-12 text-center text-slate-500">No active students found.</div>
        ) : (
          students.map(s => (
            <div key={s._id} className="card p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-slate-600 transition-colors">
              <div className="flex items-center gap-3">
                <Avatar name={s.student_name} />
                <div>
                  <p className="text-white font-medium">{s.student_name}</p>
                  <p className="text-slate-400 text-xs">{s.department || 'General'}</p>
                </div>
              </div>
              
              <div className="flex bg-slate-900 rounded-lg p-1 border border-slate-700 w-full sm:w-auto overflow-x-auto">
                {options.map(opt => (
                  <button
                    key={opt}
                    onClick={() => handleStatusChange(s._id, opt)}
                    className={`flex-1 sm:flex-none px-4 py-2 text-sm font-medium rounded-md transition-all whitespace-nowrap
                      ${attendance[s._id] === opt ? `${colors[opt]} text-white shadow-lg` : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800'}
                    `}
                  >
                    {opt}
                  </button>
                ))}
              </div>
            </div>
          ))
        )}
      </div>

      {/* Floating Action Bar */}
      {students.length > 0 && (
        <div className="fixed bottom-0 left-0 right-0 p-4 bg-slate-900/80 backdrop-blur-md border-t border-slate-800 flex justify-center z-20 md:pl-64">
          <button onClick={handleSubmit} className="btn btn-primary px-8 py-3 shadow-indigo-500/25 shadow-lg w-full max-w-xs" disabled={saving}>
            {saving ? 'Saving...' : 'Save Attendance'}
          </button>
        </div>
      )}
    </div>
  );
};

export default AttendanceMarkPage;