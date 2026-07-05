import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getAllStudents } from '../../api/students';
import { getAllSubjects } from '../../api/subjects';
import { addMarks } from '../../api/marks';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);

const MarksAddPage = () => {
  const navigate = useNavigate();
  const [students, setStudents] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  
  const [selectedSubject, setSelectedSubject] = useState('');
  const [globalTotal, setGlobalTotal] = useState(100);
  
  // Format: { studentId: { marks_obtained: 85, total_marks: 100 } }
  const [markData, setMarkData] = useState({});

  useEffect(() => {
    Promise.all([getAllStudents(), getAllSubjects()])
      .then(([stuRes, subRes]) => {
        const activeStu = (stuRes.data.data || []).filter(s => s.status === 'Active');
        setStudents(activeStu);
        setSubjects((subRes.data.data || []).filter(s => s.status === 'Active'));
        
        const initial = {};
        activeStu.forEach(s => {
          initial[s._id] = { marks_obtained: '', total_marks: 100 };
        });
        setMarkData(initial);
      })
      .catch(() => toast.error('Failed to load data'))
      .finally(() => setLoading(false));
  }, []);

  const handleGlobalTotalChange = (val) => {
    const total = Number(val) || 100;
    setGlobalTotal(total);
    setMarkData(prev => {
      const next = { ...prev };
      Object.keys(next).forEach(k => {
        next[k] = { ...next[k], total_marks: total };
      });
      return next;
    });
  };

  const handleMarkChange = (id, field, value) => {
    setMarkData(prev => ({
      ...prev,
      [id]: { ...prev[id], [field]: value }
    }));
  };

  const handleSubmit = async () => {
    if (!selectedSubject) {
      toast.error('Please select a subject first');
      return;
    }

    // Filter out empty marks
    const marksToSave = [];
    for (const stuId of Object.keys(markData)) {
      const d = markData[stuId];
      if (d.marks_obtained !== '') {
        const obt = Number(d.marks_obtained);
        const tot = Number(d.total_marks);
        if (obt > tot) {
          toast.error('Marks obtained cannot be greater than total marks');
          return;
        }
        marksToSave.push({
          student: stuId,
          subject: selectedSubject,
          marks_obtained: obt,
          total_marks: tot
        });
      }
    }

    if (marksToSave.length === 0) {
      toast.error('Please enter marks for at least one student');
      return;
    }

    setSaving(true);
    try {
      await addMarks({ marks: marksToSave });
      toast.success('Marks saved successfully!');
      navigate('/marks');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Failed to save marks');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl pb-24">
      <div className="flex items-center gap-4">
        <Link to="/marks" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Enter Marks</h1>
          <p className="page-subtitle">Bulk enter marks for a subject</p>
        </div>
      </div>

      <div className="card p-5 grid grid-cols-1 md:grid-cols-2 gap-5 sticky top-4 z-10 shadow-xl border-indigo-500/30">
        <div className="form-group mb-0">
          <label htmlFor="subject" className="label text-xs">Select Subject *</label>
          <select id="subject" className="input" value={selectedSubject} onChange={e => setSelectedSubject(e.target.value)}>
            <option value="">-- Choose Subject --</option>
            {subjects.map(s => (
              <option key={s._id} value={s._id}>{s.subject_name} ({s.subject_code})</option>
            ))}
          </select>
        </div>
        <div className="form-group mb-0">
          <label htmlFor="global_total" className="label text-xs">Default Total Marks</label>
          <input 
            id="global_total" 
            type="number" 
            className="input" 
            value={globalTotal} 
            onChange={e => handleGlobalTotalChange(e.target.value)} 
          />
        </div>
      </div>

      <div className="card">
        <div className="card-header bg-slate-900">
          <h3 className="section-title">Students List</h3>
        </div>
        <div className="table-container">
          <table className="table">
            <thead>
              <tr>
                <th>Student Name</th>
                <th className="w-48">Marks Obtained</th>
                <th className="w-48">Total Marks</th>
                <th className="w-32">% Score</th>
              </tr>
            </thead>
            <tbody>
              {students.length === 0 ? (
                <tr><td colSpan={4} className="text-center py-8 text-slate-500">No active students found.</td></tr>
              ) : (
                students.map(s => {
                  const m = markData[s._id];
                  const hasVal = m.marks_obtained !== '';
                  const pct = hasVal && m.total_marks > 0 ? ((Number(m.marks_obtained) / Number(m.total_marks)) * 100).toFixed(1) : '--';
                  const invalid = hasVal && Number(m.marks_obtained) > Number(m.total_marks);
                  
                  return (
                    <tr key={s._id}>
                      <td className="text-white font-medium text-sm">{s.student_name}</td>
                      <td>
                        <input 
                          type="number" 
                          min="0"
                          className={`input py-1 text-sm ${invalid ? 'input-error bg-red-900/20' : ''}`}
                          placeholder="e.g. 85"
                          value={m.marks_obtained}
                          onChange={e => handleMarkChange(s._id, 'marks_obtained', e.target.value)}
                        />
                      </td>
                      <td>
                        <input 
                          type="number" 
                          min="1"
                          className="input py-1 text-sm bg-slate-800/50"
                          value={m.total_marks}
                          onChange={e => handleMarkChange(s._id, 'total_marks', e.target.value)}
                        />
                      </td>
                      <td>
                        <span className={`font-mono text-sm ${invalid ? 'text-red-400' : 'text-emerald-400'}`}>
                          {pct}%
                        </span>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {students.length > 0 && (
        <div className="fixed bottom-0 left-0 right-0 p-4 bg-slate-900/80 backdrop-blur-md border-t border-slate-800 flex justify-center z-20 md:pl-64">
          <button onClick={handleSubmit} className="btn btn-primary px-8 py-3 shadow-indigo-500/25 shadow-lg w-full max-w-xs" disabled={saving}>
            {saving ? 'Saving...' : 'Save Marks'}
          </button>
        </div>
      )}
    </div>
  );
};

export default MarksAddPage;