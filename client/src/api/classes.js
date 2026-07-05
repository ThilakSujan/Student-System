import api from './axios';

export const getAllClasses = () => api.get('/classes');
export const getClass = (id) => api.get(`/classes/${id}`);
export const createClass = (data) => api.post('/classes', data);
export const updateClass = (id, data) => api.put(`/classes/${id}`, data);
export const deleteClass = (id) => api.delete(`/classes/${id}`);
export const assignStudents = (id, data) => api.post(`/classes/${id}/assign-students`, data);
export const removeStudent = (classId, studentId) => api.delete(`/classes/${classId}/students/${studentId}`);
