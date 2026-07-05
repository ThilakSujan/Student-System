import api from './axios';

export const getAllUsers = () => api.get('/users');
export const getPendingUsers = () => api.get('/users/pending');
export const approveUser = (id) => api.put(`/users/${id}/approve`);
export const rejectUser = (id, reason) => api.put(`/users/${id}/reject`, { reason });
export const deleteUser = (id) => api.delete(`/users/${id}`);
