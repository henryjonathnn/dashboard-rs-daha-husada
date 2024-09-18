import React from 'react';
import { Head, usePage, useForm } from '@inertiajs/react';

export default function Index({ initialComplaints, availableDates }) {
    const { data, setData, get } = useForm({
        selectedDate: `${availableDates[0]?.year}-${availableDates[0]?.month}`
    });

    const handleDateChange = (e) => {
        setData('selectedDate', e.target.value);
        get(route('complaints.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const { complaints } = usePage().props;

    return (
        <>
            <Head title="Complaints Dashboard" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <h1 className="text-2xl font-semibold mb-6">Complaints Dashboard</h1>
                            <div className="mb-4">
                                <select
                                    value={data.selectedDate}
                                    onChange={handleDateChange}
                                    className="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    {availableDates.map((date) => (
                                        <option key={`${date.year}-${date.month}`} value={`${date.year}-${date.month}`}>
                                            {date.monthName} {date.year}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date In</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Processing</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Completed</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Officer</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Is Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {complaints.map((complaint) => (
                                            <tr key={complaint.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.id}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.nama_pelapor}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.unit}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.lokasi}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.status}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.datetime_masuk}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.datetime_pengerjaan || 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.datetime_selesai || 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.petugas || 'N/A'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{complaint.is_pending ? 'Yes' : 'No'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}