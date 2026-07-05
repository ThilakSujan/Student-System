import api from './axios';

export const getAttendance = (params) => api.get('/attendance', { params });
export const markAttendance = (data) => api.post('/attendance/bulk-mark', data);
export const updateAttendance = (id, data) => api.put(`/attendance/${id}`, data);
export const getStudentAttendance = (studentId) => api.get(`/attendance/student/${studentId}`);
