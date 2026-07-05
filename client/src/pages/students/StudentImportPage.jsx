import { useState, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../../api/axios';

const BackIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-4 h-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
  </svg>
);
const UploadIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="w-8 h-8">
    <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
  </svg>
);

const StudentImportPage = () => {
  const navigate = useNavigate();
  const fileRef = useRef(null);
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [dragging, setDragging] = useState(false);

  const handleDrop = (e) => {
    e.preventDefault();
    setDragging(false);
    const dropped = e.dataTransfer.files[0];
    if (dropped?.name.endsWith('.csv')) setFile(dropped);
    else toast.error('Please upload a CSV file');
  };

  const handleUpload = async () => {
    if (!file) return toast.error('Please select a CSV file first');
    const formData = new FormData();
    formData.append('file', file);
    setUploading(true);
    try {
      const res = await api.post('/students/import-csv', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      toast.success(res.data.message || 'Students imported!');
      navigate('/students');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Import failed');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-6 animate-fade-in max-w-2xl">
      <div className="flex items-center gap-4">
        <Link to="/students" className="btn btn-ghost btn-sm text-slate-400 hover:text-white">
          <BackIcon /> Back
        </Link>
        <div>
          <h1 className="page-title">Import Students</h1>
          <p className="page-subtitle">Bulk-upload students via a CSV file</p>
        </div>
      </div>

      {/* CSV Format guide */}
      <div className="card p-5 border-indigo-500/20">
        <h2 className="section-title mb-3">CSV Format</h2>
        <p className="text-slate-400 text-sm mb-3">Your CSV file must have the following columns in the header row:</p>
        <div className="bg-slate-900 rounded-lg p-3 font-mono text-xs text-indigo-300 overflow-x-auto">
          student_name,email,phone,gender,dob,department,parent_name,parent_email,status,skills
        </div>
        <p className="text-slate-500 text-xs mt-2">Skills should be comma-separated within double quotes, e.g. <code className="text-amber-400">"Python,Music"</code></p>
      </div>

      {/* Drop zone */}
      <div
        className={`card p-10 text-center border-2 border-dashed transition-all duration-200 cursor-pointer ${dragging ? 'border-indigo-500 bg-indigo-500/10' : 'border-slate-600 hover:border-indigo-500/50'}`}
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={handleDrop}
        onClick={() => fileRef.current?.click()}
      >
        <input ref={fileRef} type="file" accept=".csv" className="hidden" onChange={e => setFile(e.target.files[0])} />
        <div className="flex flex-col items-center gap-3 text-slate-400">
          <UploadIcon />
          {file ? (
            <>
              <p className="text-white font-medium">{file.name}</p>
              <p className="text-slate-500 text-sm">{(file.size / 1024).toFixed(1)} KB</p>
            </>
          ) : (
            <>
              <p className="text-sm font-medium">Drag & drop your CSV file here</p>
              <p className="text-xs text-slate-500">or click to browse</p>
            </>
          )}
        </div>
      </div>

      <div className="flex gap-3 justify-end">
        {file && (
          <button className="btn btn-secondary" onClick={() => setFile(null)}>Clear</button>
        )}
        <button
          id="import-students"
          className="btn btn-primary"
          onClick={handleUpload}
          disabled={!file || uploading}
        >
          {uploading ? <><span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" /> Importing…</> : 'Import Students'}
        </button>
      </div>
    </div>
  );
};

export default StudentImportPage;
