import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import Logo from '../assets/logo.png';
import { FaBars, FaTimes, FaUpload } from 'react-icons/fa';
import { FaChartSimple, FaScrewdriverWrench } from "react-icons/fa6";
import { BsPersonWorkspace } from "react-icons/bs";
import { GiFirstAidKit } from "react-icons/gi";
import { MdKeyboardArrowDown, MdKeyboardArrowUp } from 'react-icons/md';

export default function Navbar() {
  const [isOpen, setIsOpen] = useState(false);
  const [activeLink, setActiveLink] = useState('');
  const [openMenu, setOpenMenu] = useState('');

  useEffect(() => {
    setActiveLink(window.location.pathname);
  }, []);

  const toggleMenu = () => {
    setIsOpen(!isOpen);
  };

  const toggleDropdown = (link) => {
    setOpenMenu(openMenu === link ? '' : link);
  };

  const menuItems = [
    {
      name: 'Dashboard',
      icon: <FaChartSimple />,
      link: '/',
    },
    {
      name: 'Data Komplain IT',
      icon: <FaScrewdriverWrench />,
      link: '/komplain',
      subMenu: [
        { name: 'Data Unit', icon: <GiFirstAidKit />, link: '/komplain/data-unit' },
        { name: 'Data Kinerja', icon: <BsPersonWorkspace />, link: '/komplain/data-kinerja' }
      ]
    },
    {
      name: 'Permintaan Update Data IT',
      icon: <FaUpload />,
      link: '/permintaan-update',
      subMenu: [
        { name: 'Data Kinerja', icon: <BsPersonWorkspace />, link: '/permintaan-update/data-kinerja' }
      ]
    }
  ];

  return (
    <div className="navbar bg-white shadow-md fixed w-full z-50 lg:hidden">
      <div className="flex justify-between items-center p-4">
        <img src={Logo} alt="Logo" className="w-12 h-12" />
        <button
          onClick={toggleMenu}
          className="text-2xl text-gray-600 focus:outline-none"
        >
          {isOpen ? <FaTimes /> : <FaBars />}
        </button>
      </div>
      <div className={`transition-all duration-300 ${isOpen ? 'max-h-screen' : 'max-h-0 overflow-hidden'}`}>
        <nav className="flex flex-col p-4">
          {menuItems.map((item, index) => (
            <div key={index} className="mb-2">
              <div className="flex items-center justify-between">
                <Link
                  href={item.link}
                  className={`flex items-center py-2 text-lg transition-colors duration-300 ${
                    activeLink === item.link ? 'text-light-green' : 'text-gray-700'
                  }`}
                  onClick={() => {
                    setActiveLink(item.link);
                    if (!item.subMenu) {
                      toggleMenu();
                    }
                  }}
                >
                  <span className="mr-3">{item.icon}</span>
                  {item.name}
                </Link>
                {item.subMenu && (
                  <button
                    onClick={() => toggleDropdown(item.link)}
                    className="p-2 text-gray-600 focus:outline-none"
                  >
                    {openMenu === item.link ? <MdKeyboardArrowUp /> : <MdKeyboardArrowDown />}
                  </button>
                )}
              </div>
              {item.subMenu && openMenu === item.link && (
                <div className="ml-8 mt-2">
                  {item.subMenu.map((subItem, subIndex) => (
                    <Link
                      key={subIndex}
                      href={subItem.link}
                      className={`flex items-center py-1 text-lg transition-colors duration-300 ${
                        activeLink === subItem.link ? 'text-light-green' : 'text-gray-700'
                      }`}
                      onClick={() => {
                        setActiveLink(subItem.link);
                        toggleMenu();
                      }}
                    >
                      <span className="mr-2">{subItem.icon}</span>
                      {subItem.name}
                    </Link>
                  ))}
                </div>
              )}
            </div>
          ))}
        </nav>
      </div>
    </div>
  );
}