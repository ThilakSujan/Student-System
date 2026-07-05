import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { getClass, assignStudents, removeStudent } from '../../api/classes';
import { getAllStudents } from '../../api/students';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);
const XMarkIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
  </svg>
);

const ClassAssignPage = () => {
  const { id } = useParams();
  const [classData, setClassData] = useState(null);
  const [allStudents, setAllStudents] = useState([]);
  const [loading, setLoading] = useState(true);
  
  // Selection state for right pane
  const [selectedIds, setSelectedIds] = useState(new Set());
  const [search, setSearch] = useState('');

  const fetchData = async () => {
    try {
      const [classRes, studentsRes] = await Promise.all([
        getClass(id),
        getAllStudents()
      ]);
      setClassData(classRes.data.data);
      setAllStudents(studentsRes.data.data);
      setSelectedIds(new Set()); // reset selection
    } catch {
      toast.error('Failed to load data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchData(); }, [id]);

  const handleRemove = async (studentId) => {
    try {
      await removeStudent(id, studentId);
      toast.success('Student removed from class');
      fetchData();
    } catch {
      toast.error('Failed to remove student');
    }
  };

  const handleAssign = async () => {
    if (selectedIds.size === 0) return;
    try {
      await assignStudents(id, { studentIds: Array.from(selectedIds) });
      toast.success('Students assigned successfully');
      fetchData();
    } catch {
      toast.error('Failed to assign students');
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  // Derive lists
  const enrolledIds = new Set((classData.students || []).map(s => s._id));
  const available = allStudents.filter(s => !enrolledIds.has(s._id) && s.status === 'Active');
  
  const filteredAvailable = available.filter(s => {
    const term = search.toLowerCase();
    return !search || s.student_name?.toLowerCase().includes(term) || s.email?.toLowerCase().includes(term);
  });

  const toggleSelect = (sId) => {
    const next = new Set(selectedIds);
    if (next.has(sId)) next.delete(sId);
    else next.add(sId);
    setSelectedIds(next);
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-5xl">
      <div className="flex items-center gap-4">
        <Link to="/classes" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Manage Students</h1>
          <p className="page-subtitle">{classData.class_name} {classData.section ? `- ${classData.section}` : ''} ({classData.academic_year})</p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* LEFT PANE: Enrolled */}
        <div className="card flex flex-col h-[600px]">
          <div className="card-header bg-slate-900 border-b border-slate-700">
            <h3 className="section-title text-emerald-400">Enrolled Students ({classData.students?.length || 0})</h3>
          </div>
          <div className="flex-1 overflow-y-auto p-2">
            {classData.students?.length === 0 ? (
              <div className="text-center p-8 text-slate-500">No students enrolled in this class yet.</div>
            ) : (
              <ul className="space-y-2">
                {classData.students?.map(s => (
                  <li key={s._id} className="flex items-center justify-between p-3 rounded-lg bg-slate-800/50 border border-slate-700/50">
                    <div>
                      <p className="text-white text-sm font-medium">{s.student_name}</p>
                      <p className="text-slate-400 text-xs">{s.email}</p>
                    </div>
                    <button 
                      onClick={() => handleRemove(s._id)}
                      className="btn-icon btn-ghost text-slate-400 hover:text-red-400"
                      title="Remove from class"
                    >
                      <XMarkIcon />
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        {/* RIGHT PANE: Available */}
        <div className="card flex flex-col h-[600px]">
          <div className="card-header bg-slate-900 border-b border-slate-700 flex flex-col gap-3">
            <div className="flex justify-between items-center">
              <h3 className="section-title text-indigo-400">Available Students</h3>
              {selectedIds.size > 0 && (
                <button onClick={handleAssign} className="btn btn-primary btn-sm">
                  Add {selectedIds.size} Selected
                </button>
              )}
            </div>
            <input 
              type="text" 
              className="input bg-slate-950 text-sm" 
              placeholder="Search available students..."
              value={search}
              onChange={e => setSearch(e.target.value)}
            />
          </div>
          <div className="flex-1 overflow-y-auto p-2">
            {filteredAvailable.length === 0 ? (
              <div className="text-center p-8 text-slate-500">No available students found.</div>
            ) : (
              <ul className="space-y-2">
                {filteredAvailable.map(s => (
                  <li 
                    key={s._id} 
                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors
                      ${selectedIds.has(s._id) ? 'bg-indigo-900/30 border-indigo-500/50' : 'bg-slate-800/50 border-slate-700/50 hover:bg-slate-800'}
                    `}
                    onClick={() => toggleSelect(s._id)}
                  >
                    <input 
                      type="checkbox" 
                      className="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-800"
                      checked={selectedIds.has(s._id)}
                      onChange={() => {}} 
                    />
                    <div>
                      <p className="text-white text-sm font-medium">{s.student_name}</p>
                      <p className="text-slate-400 text-xs">{s.email}</p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default ClassAssignPage;