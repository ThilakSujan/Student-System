import api from './axios';

export const getAllMarks = (params) => api.get('/marks', { params });
export const addMarks = (data) => api.post('/marks', data);
export const updateMark = (id, data) => api.put(`/marks/${id}`, data);
export const deleteMark = (id) => api.delete(`/marks/${id}`);
export const publishMarks = (markIds) => api.post('/marks/publish', { markIds });
export const getStudentMarks = (studentId) => api.get(`/marks/student/${studentId}`);
